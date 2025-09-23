<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Fetch all orders with their items
    public function index()
    {
        $orders = Order::with('items')->get();

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
                    'notes' => $request->invoice['notes'],
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
                        'payment_status' => 'pending',
                        'total' => 0,
                    ]);

                    $orderTotal = 0;
                    foreach ($orderData['items'] as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'item_id' => $item['item_id'],
                            'item_name' => $item['item_name'],
                            'price' => $item['price'],
                            'quantity' => $item['quantity'],

                        ]);

                        $orderTotal += $item['price'] * $item['quantity'];
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

    // Update order including payment
    public function update(Request $request, Order $order)
    {
        // Handle image upload if present
        if ($request->hasFile('payment_image')) {
            $imagePath = $request->file('payment_image')->store('payments', 'public');
            $request->merge(['payment_image' => $imagePath]);
        }


        // If payment method & total exist, mark as paid
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
            'courier',
            'delivery_fee',
            'delivery_status',
            'total'
        ]));

        return response()->json(['message' => 'Order updated', 'order' => $order]);
    }

    // Delete order
    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }
}
