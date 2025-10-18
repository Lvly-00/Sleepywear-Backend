<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Item;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // Fetch all orders with their items (unpaid on top)
    public function index()
    {
        $orders = Order::with('items')
            ->orderByRaw("CASE WHEN payment_status = 'unpaid' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        // Add payment image URL if exists
        $orders->map(function ($order) {
            $order->payment_image_url = $order->payment_image
                ? asset('storage/' . $order->payment_image)
                : null;
            return $order;
        });

        return response()->json($orders);
    }

    // Create invoice + orders + items in one request
    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                $invoice = Invoice::create([
                    'customer_name' => $request->invoice['customer_name'],
                    'notes' => $request->invoice['notes'] ?? null,
                    'status' => 'draft',
                    'total' => 0,
                ]);

                $total = 0;

                foreach ($request->orders as $orderData) {
                    $order = Order::create([
                        'first_name' => $orderData['first_name'],
                        'last_name' => $orderData['last_name'],
                        'address' => $orderData['address'],
                        'contact_number' => $orderData['contact_number'],
                        'social_handle' => $orderData['social_handle'],
                        'invoice_id' => $invoice->id,
                        'payment_status' => 'unpaid',
                        'total' => 0,
                    ]);

                    $orderTotal = 0;
                    foreach ($orderData['items'] as $itemData) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'item_id' => $itemData['item_id'],
                            'item_name' => $itemData['item_name'],
                            'price' => $itemData['price'],
                            'quantity' => $itemData['quantity'],
                        ]);

                        Item::where('id', $itemData['item_id'])
                            ->update(['status' => 'taken']);

                        $orderTotal += $itemData['price'] * $itemData['quantity'];
                    }

                    $order->update(['total' => $orderTotal]);
                    $total += $orderTotal;
                }

                $invoice->update(['total' => $total]);

                return $invoice->load('orders.items');
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // Show single order
    public function show(Order $order)
    {
        $order->load('items');
        return response()->json($order);
    }

    // Update order including payment details
    public function update(Request $request, Order $order)
    {
        if ($request->hasFile('payment_image')) {
            $imagePath = $request->file('payment_image')->store('payments', 'public');
            $request->merge(['payment_image' => $imagePath]);
        }

        if ($request->filled('payment_method') && $request->filled('total')) {
            $request->merge(['payment_status' => 'paid']);
        }

        $order->update($request->only([
            'first_name',
            'last_name',
            'address',
            'contact_number',
            'social_handle',
            'payment_method',
            'payment_image',
            'payment_status',
            'total'
        ]));

        return response()->json(['message' => 'Order updated', 'order' => $order]);
    }

    // Delete order
    public function destroy(Order $order)
    {
        try {
            // Get all items in the order
            $items = $order->items;

            // Update each item's status to "available"
            foreach ($items as $orderItem) {
                $item = $orderItem->item; // Ensure relationship exists
                if ($item) {
                    $item->update(['status' => 'available']);
                }
            }

            // Delete order items first
            $order->items()->delete();

            // Then delete the order
            $order->delete();

            return response()->json(['message' => 'Order deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Order delete failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete order',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
