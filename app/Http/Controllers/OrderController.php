<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\OrderItemCustomer;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // List orders (ascending)
    public function index(Request $request)
    {

        $orders = Order::with('items.customers')->orderBy('order_date', 'asc')->paginate(25);
        return response()->json($orders);
    }

    // Create invoice + orders + items in one request
    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice.customer_name' => 'nullable|string',
            'invoice.notes' => 'nullable|string',
            'orders' => 'required|array|min:1',
            'orders.*.first_name' => 'required|string',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_name' => 'required|string',
            'orders.*.items.*.price' => 'required|numeric',
            'orders.*.items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $invoiceRef = 'INV-' . strtoupper(Str::random(8));
            $invoice = Invoice::create([
                'invoice_ref' => $invoiceRef,
                'issue_date' => $request->input('invoice.issue_date') ?? now(),
                'sent_date' => $request->input('invoice.sent_date'),
                'customer_name' => $request->input('invoice.customer_name') ?? null,
                'notes' => $request->input('invoice.notes'),
                'status' => 'sent',
            ]);

            $invoiceTotal = 0;
            foreach ($request->orders as $ordPayload) {
                $order = Order::create([
                    'invoice_id' => $invoice->id,
                    'first_name' => $ordPayload['first_name'],
                    'last_name' => $ordPayload['last_name'] ?? null,
                    'address' => $ordPayload['address'] ?? null,
                    'contact_number' => $ordPayload['contact_number'] ?? null,
                    'social_handle' => $ordPayload['social_handle'] ?? null,
                    'payment_image' => $ordPayload['payment_image'] ?? null,
                    'payment_method' => $ordPayload['payment_method'] ?? null,
                    'courier' => $ordPayload['courier'] ?? null,
                    'delivery_fee' => $ordPayload['delivery_fee'] ?? 0,
                    'total' => 0,
                ]);

                $orderTotal = 0;
                foreach ($ordPayload['items'] as $itemPayload) {
                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemPayload['product_id'] ?? null,
                        'product_name' => $itemPayload['product_name'],
                        'price' => $itemPayload['price'],
                        'quantity' => $itemPayload['quantity'] ?? 1,
                        'notes' => $itemPayload['notes'] ?? null,
                        'status' => 'pending',
                    ]);

                    // Register this order as a customer/booker for this item
                    // check existing bookings count for this product (global across orders?)
                    // Here we limit per item instance (per order_item) to 3 customers total.
                    OrderItemCustomer::create([
                        'order_item_id' => $item->id,
                        'order_id' => $order->id,
                        'role' => 'holder',
                        'position' => null
                    ]);

                    $orderTotal += ($item->price * $item->quantity);
                }

                $order->total = $orderTotal + ($order->delivery_fee ?? 0);
                $order->save();

                $invoiceTotal += $order->total;
            }

            $invoice->total = $invoiceTotal;
            $invoice->save();

            DB::commit();

            return response()->json(['message' => 'Invoice and orders created', 'invoice_ref' => $invoice->invoice_ref, 'invoice_id' => $invoice->id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create invoice', 'details' => $e->getMessage()], 500);
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
