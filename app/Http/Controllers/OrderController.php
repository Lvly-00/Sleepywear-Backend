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
        $perPage = 10; // 10 orders per page
        $page = $request->query('page', 1);

        // Paginate orders
        $ordersQuery = Order::with(['items', 'payment'])
            ->orderByRaw("CASE WHEN id IN (SELECT order_id FROM payments WHERE payment_status='Unpaid') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc');

        $orders = $ordersQuery->paginate($perPage, ['*'], 'page', $page);

        // Format each order
        $orders->getCollection()->transform(function ($order) {
            $order->payment_image_url = $order->payment && $order->payment->payment_image
                ? asset('storage/'.$order->payment->payment_image)
                : null;

            $order->formatted_id = str_pad($order->id, 4, '0', STR_PAD_LEFT);

            return $order;
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $customerData = $request->input('customer');
                $itemsData = $request->input('items');

                // Find or create customer
                $customer = isset($customerData['id']) && $customerData['id']
                    ? Customer::findOrFail($customerData['id'])
                    : Customer::create($customerData);

                if (isset($customerData['id']) && $customerData['id']) {
                    $customer->update($customerData);
                }

                // Create order
                $order = Order::create([
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
                    ]);

                    // Update item status to Sold Out
                    Item::where('id', $itemData['item_id'])->update(['status' => 'Sold Out']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                // Create initial payment record (unpaid)
                Payment::create([
                    'order_id' => $order->id,
                    'payment_status' => 'Unpaid',
                    'total_paid' => 0,
                ]);

                // Create invoice
                Invoice::create([
                    'order_id' => $order->id,
                    'total' => $orderTotal,
                    'status' => 'Draft',
                ]);

                $order->load(['items', 'payment']);
                $order->formatted_id = str_pad($order->id, 4, '0', STR_PAD_LEFT);

                return $order;
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Order $order)
    {
        $order->load(['items', 'payment']);
        $order->formatted_id = str_pad($order->id, 4, '0', STR_PAD_LEFT);

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
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
            $order->formatted_id = str_pad($order->id, 4, '0', STR_PAD_LEFT);

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
        try {
            return DB::transaction(function () use ($request, $order) {
                // Revert old items to available
                foreach ($order->items as $orderItem) {
                    $item = $orderItem->item;
                    if ($item) {
                        $item->update(['status' => 'Available']);
                    }
                }

                // Remove existing items
                $order->items()->delete();

                $orderTotal = 0;

                foreach ($request->input('items') as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'item_name' => $itemData['item_name'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                    ]);

                    // Mark new items as sold out
                    Item::where('id', $itemData['item_id'])->update(['status' => 'Sold Out']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                if ($order->invoice) {
                    $order->invoice->update(['total' => $orderTotal]);
                }

                $order->load(['items', 'payment']);
                $order->formatted_id = str_pad($order->id, 4, '0', STR_PAD_LEFT);

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

    public function destroy(Order $order)
    {
        DB::beginTransaction();

        try {
            $order->load('invoice', 'payment', 'items'); // ensure relationships are loaded
            $isPaid = $order->payment && $order->payment->payment_status === 'Paid';

            if (! $isPaid) {
                foreach ($order->items as $orderItem) {
                    $item = $orderItem->item;
                    if ($item) {
                        $item->update(['status' => 'Available']);
                    }
                }
                // delete invoice if unpaid
                if ($order->invoice) {
                    $order->invoice->delete();
                }
            }

            $order->items()->delete();

            if ($order->payment) {
                $order->payment->delete();
            }

            $order->delete(); // now safe, invoice is already handled

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
