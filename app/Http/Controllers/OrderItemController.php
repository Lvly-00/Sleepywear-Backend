<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;

class OrderItemController extends Controller
{
    /**
     * List paginated order items with related order and customer.
     */
    public function index()
    {
        // Eager load order and customer via order.customer
        $items = OrderItem::with(['order.customer'])
            ->orderBy('created_at', 'asc')
            ->paginate(25);

        return response()->json($items);
    }

    /**
     * Show a single order item with related order and customer.
     */
    public function show(OrderItem $orderItem)
    {
        $orderItem->load('order.customer');

        return response()->json($orderItem);
    }

    /**
     * Get the customer for the order item.
     */
    public function customer(OrderItem $orderItem)
    {
        // Access the customer through the order
        $customer = $orderItem->order ? $orderItem->order->customer : null;

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json($customer);
    }
}
