<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Fetch all orders with their items and item details
    public function index()
    {
        $orders = Order::with('items.item')->get();
        return response()->json($orders);
    }

    // Create invoice + orders + items in one request
    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $invoice = Invoice::create([
                    'customer_name'   => $request->invoice['customer_name'],
                    'notes'           => $request->invoice['notes'],
                    'status'          => 'draft', // or pending if you added enum
                    'total'           => 0, // will calculate below
                ]);

                $total = 0;
                foreach ($request->orders as $orderData) {
                    $order = Order::create([
                        'first_name'      => $orderData['first_name'],
                        'last_name'       => $orderData['last_name'],
                        'address'         => $orderData['address'],
                        'contact_number'  => $orderData['contact_number'],
                        'social_handle'   => $orderData['social_handle'],
                        'invoice_id'      => $invoice->id,
                    ]);

                    foreach ($orderData['items'] as $item) {
                        OrderItem::create([
                            'order_id'   => $order->id,
                            'item_id' => $item['item_id'],
                            'item_name' => $item['item_name'],
                            'price'      => $item['price'],
                            'quantity'   => $item['quantity'],
                        ]);
                        $total += $item['price'] * $item['quantity'];
                    }
                }

                $invoice->update(['total' => $total]);

                return $invoice->load('orders.items');
            });
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Order creation failed',
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }


    // show single order
    public function show(Order $order)
    {
        $order->load('items.customers');
        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->only(['first_name', 'last_name', 'address', 'contact_number', 'social_handle', 'payment_method', 'payment_image', 'payment_status', 'courier', 'delivery_fee', 'delivery_status']));
        return response()->json(['message' => 'Order updated', 'order' => $order]);
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }
}
