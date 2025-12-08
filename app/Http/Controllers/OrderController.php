<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;
        $page = $request->query('page', 1);
        $search = $request->query('search');

        $driver = DB::connection()->getDriverName();

        $ordersQuery = Order::with(['items', 'payment'])
            ->where('orders.user_id', auth()->id())
            ->leftJoin('payments', 'orders.id', '=', 'payments.order_id')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select('orders.*')
            ->orderByRaw("
                CASE WHEN payments.payment_status = 'Paid' THEN 1 ELSE 0 END ASC,
                CASE WHEN payments.payment_status = 'Paid' THEN orders.order_date END ASC,
                CASE WHEN payments.payment_status != 'Paid' THEN orders.order_date END DESC
            ");

        if ($search) {
            $ordersQuery->where(function ($query) use ($search, $driver) {
                // CHANGED: Search 'order_number' instead of 'id'
                if ($driver === 'pgsql') {
                    $query->where('customers.first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('customers.last_name', 'ILIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, '')) ILIKE ?", ["%{$search}%"])
                        ->orWhereRaw('CAST(orders.order_number AS TEXT) ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw("LPAD(CAST(orders.order_number AS TEXT), 4, '0') ILIKE ?", ["%{$search}%"]);
                } elseif ($driver === 'sqlite') {
                    $query->where('customers.first_name', 'LIKE', "%{$search}%")
                        ->orWhere('customers.last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("(COALESCE(customers.first_name, '') || ' ' || COALESCE(customers.last_name, '')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw('CAST(orders.order_number AS TEXT) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw("printf('%04d', orders.order_number) LIKE ?", ["%{$search}%"]);
                } else {
                    $query->where('customers.first_name', 'LIKE', "%{$search}%")
                        ->orWhere('customers.last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, '')) LIKE ?", ["%{$search}%"])
                        ->orWhere('orders.order_number', 'LIKE', "%{$search}%")
                        ->orWhereRaw("LPAD(orders.order_number, 4, '0') LIKE ?", ["%{$search}%"]);
                }
            });
        }

        $orders = $ordersQuery->paginate($perPage, ['*'], 'page', $page)
            ->appends(['search' => $search]);

        $orders->getCollection()->transform(function ($order) {
            $order->payment_image_url = $order->payment && $order->payment->payment_image
                ? asset('storage/'.$order->payment->payment_image)
                : null;

            // CHANGED: Pad order_number, not id
            $order->formatted_id = str_pad($order->order_number, 4, '0', STR_PAD_LEFT);

            return $order;
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        // 1. Validate inputs before starting transaction
        // This ensures all item_ids sent actually exist in the DB.
        $request->validate([
            'customer' => 'required|array',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id', // <--- PREVENTS THE CRASH
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $customerData = $request->input('customer');
                $itemsData = $request->input('items');

                // Customer Logic
                $customer = isset($customerData['id']) && $customerData['id']
                    ? Customer::findOrFail($customerData['id'])
                    : Customer::create($customerData);

                if (isset($customerData['id']) && $customerData['id']) {
                    $customer->update($customerData);
                }

                // Order Number Logic (Fixed for Postgres/SQLite compatibility)
                $lastOrder = Order::where('user_id', auth()->id())
                    ->orderBy('order_number', 'desc')
                    ->lockForUpdate()
                    ->first();
                $nextOrderNumber = $lastOrder ? $lastOrder->order_number + 1 : 1;

                // Create Order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'order_number' => $nextOrderNumber,
                    'customer_id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'address' => $customer->address,
                    'contact_number' => $customer->contact_number,
                    'social_handle' => $customer->social_handle,
                    'total' => 0,
                ]);

                $orderTotal = 0;

                foreach ($itemsData as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'item_name' => $itemData['item_name'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'user_id' => auth()->id(),
                    ]);

                    Item::where('id', $itemData['item_id'])->update(['status' => 'Reserved']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_status' => 'Unpaid',
                    'total_paid' => 0,
                ]);

                Invoice::create([
                    'order_id' => $order->id,
                    'total' => $orderTotal,
                    'status' => 'Draft',
                    'user_id' => auth()->id(),
                ]);

                $order->load(['items', 'payment']);
                $order->formatted_id = str_pad($order->order_number, 4, '0', STR_PAD_LEFT);

                return $order;
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation errors pass through nicely
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->load(['items', 'payment']);
        // CHANGED: Use order_number
        $order->formatted_id = str_pad($order->order_number, 4, '0', STR_PAD_LEFT);

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $customerData = $request->input('customer');

            if ($customerData) {
                $order->customer->update($customerData);

                $order->update([
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'address' => $customerData['address'],
                    'contact_number' => $customerData['contact_number'],
                    'social_handle' => $customerData['social_handle'],
                ]);
            }

            $order->load(['items', 'payment']);
            // CHANGED: Use order_number
            $order->formatted_id = str_pad($order->order_number, 4, '0', STR_PAD_LEFT);

            return response()->json([
                'message' => 'Order updated',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order update failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateItems(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            return DB::transaction(function () use ($request, $order) {
                // 1. Revert old items to Available
                foreach ($order->items as $orderItem) {
                    $item = $orderItem->item;
                    if ($item) {
                        $item->update(['status' => 'Available']);
                    }
                }

                // 2. Clear old items
                $order->items()->delete();

                $orderTotal = 0;

                // 3. Add new items
                foreach ($request->input('items') as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'item_name' => $itemData['item_name'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'user_id' => auth()->id(),
                    ]);

                    // UPDATED: Set to 'Reserved' (Wait for payment to mark Sold Out)
                    Item::where('id', $itemData['item_id'])->update(['status' => 'Reserved']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                if ($order->invoice) {
                    $order->invoice->update(['total' => $orderTotal]);
                }

                $order->load(['items', 'payment']);
                $order->formatted_id = str_pad($order->order_number, 4, '0', STR_PAD_LEFT);

                return response()->json([
                    'message' => 'Order items updated successfully',
                    'order' => $order,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update order items',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // destroy method remains the same as in your previous code
    public function destroy(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $order->load('invoice', 'payment', 'items');
            $isPaid = $order->payment && $order->payment->payment_status === 'Paid';

            if (! $isPaid) {
                foreach ($order->items as $orderItem) {
                    $item = $orderItem->item;
                    if ($item) {
                        $item->update(['status' => 'Available']);
                    }
                }
                if ($order->invoice) {
                    $order->invoice->delete();
                }
            }

            $order->items()->delete();

            if ($order->payment) {
                $order->payment->delete();
            }

            $order->delete();

            DB::commit();

            return response()->json([
                'message' => $isPaid
                    ? 'Paid order deleted; payment removed, invoice retained'
                    : 'Unpaid order and invoice deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order deletion failed: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to delete order',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
