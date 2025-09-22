<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function index()
    {
        $items = OrderItem::with('order', 'customers.order')->orderBy('created_at', 'asc')->paginate(25);
        return response()->json($items);
    }

    public function show(OrderItem $item)
    {
        $item->load('customers.order');
        return response()->json($item);
    }

    public function customers(OrderItem $item)
    {
        $customers = $item->customers()->with('order')->orderBy('created_at', 'asc')->get();
        return response()->json($customers);
    }
}
