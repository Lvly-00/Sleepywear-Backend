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
        try {
            $perPage = $request->input('per_page', 15);
            $search = trim($request->input('search', ''));

            // Base Query
            $query = Order::with(['items.item', 'payment'])
                ->where('orders.user_id', auth()->id())
                ->leftJoin('payments', 'orders.id', '=', 'payments.order_id')
                ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
                ->select([
                    'orders.id',
                    'orders.order_number',
                    'orders.order_date',
                    'orders.total',
                    'orders.address',
                    'orders.created_at',
                    'customers.first_name as cust_fn',
                    'customers.last_name as cust_ln',
                    'customers.contact_number as cust_phone',
                    'customers.social_handle as cust_social',
                    'payments.payment_status as pay_stat',
                ]);

            // Search Logic
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $term = "%{$search}%";

                    // SQLite uses LIKE (not ILIKE)
                    $q->where('customers.first_name', 'LIKE', $term)
                        ->orWhere('customers.last_name', 'LIKE', $term);

                    // SQLite concatenation uses ||
                    // SQLite casting uses CAST(column AS TEXT)
                    $q->orWhereRaw("COALESCE(customers.first_name, '') || ' ' || COALESCE(customers.last_name, '') LIKE ?", [$term])
                        ->orWhereRaw('CAST(orders.order_number AS TEXT) LIKE ?', [$term]);
                });
            }

            // Paginate
            $orders = $query
                ->orderByRaw("
        CASE
            WHEN payments.payment_status IS NULL THEN 0
            WHEN payments.payment_status = 'Unpaid' THEN 0
            WHEN payments.payment_status = 'Paid' THEN 1
            ELSE 2
        END ASC
    ")
                ->orderBy('orders.id', 'desc')
                ->cursorPaginate($perPage);

            // Transform Data
            $transformed = collect($orders->items())->map(function ($order) {
                $lastItem = $order->items ? $order->items->last() : null;

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'formatted_id' => str_pad($order->order_number, 4, '0', STR_PAD_LEFT),
                    'first_name' => $order->cust_fn,
                    'last_name' => $order->cust_ln,
                    'customer_full_name' => trim(($order->cust_fn ?? '').' '.($order->cust_ln ?? '')),
                    'address' => $order->address,
                    'contact_number' => $order->cust_phone,
                    'social_handle' => $order->cust_social,
                    'payment_status' => $order->pay_stat ?? 'Unpaid',
                    'order_date' => $order->order_date,
                    'total' => $order->total,
                    'items' => $order->items,
                    'payment' => $order->payment,
                    'last_item_image' => ($lastItem && $lastItem->item) ? $lastItem->item->image : null,
                ];
            });

            return response()->json([
                'data' => $transformed,
                'next_cursor' => $orders->nextCursor() ? $orders->nextCursor()->encode() : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Order Index Error: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required|array',
            'address' => 'required|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $customerData = $request->input('customer');
                $itemsData = $request->input('items');
                $shippingAddress = $request->input('address'); // Get address from root of request

                // 1. Handle Customer (Filter out address to prevent crash)
                $customerFiltered = collect($customerData)->except(['address', 'addresses'])->toArray();

                $customer = isset($customerData['id']) && $customerData['id']
                    ? Customer::findOrFail($customerData['id'])
                    : Customer::create(array_merge($customerFiltered, ['user_id' => auth()->id()]));

                if (isset($customerData['id']) && $customerData['id']) {
                    $customer->update($customerFiltered);
                }

                // 2. Order Number Logic
                $lastOrder = Order::where('user_id', auth()->id())->orderBy('order_number', 'desc')->first();
                $nextOrderNumber = $lastOrder ? $lastOrder->order_number + 1 : 1;

                // 3. Create Order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'order_number' => $nextOrderNumber,
                    'customer_id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'contact_number' => $customer->contact_number,
                    'social_handle' => $customer->social_handle,
                    'address' => $shippingAddress, // FIX: Use the variable from request, NOT $customer->address
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

        $customerData = $request->input('customer');
        $shippingAddress = $request->input('address');
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $customerData = $request->input('customer');

            if ($customerData) {
                $customerFiltered = collect($customerData)->except(['address', 'addresses'])->toArray();
                $order->customer->update($customerFiltered);

                $order->update([
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'contact_number' => $customerData['contact_number'],
                    'social_handle' => $customerData['social_handle'],
                    'address' => $shippingAddress ?? $order->address, // FIX: Use request address
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
