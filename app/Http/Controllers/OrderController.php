<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // List orders (ascending)
    public function index(Request $request)
    {

        $orders = Order::with('items.customers')->orderBy('order_date', 'asc')->paginate(25);
        return response()->json($orders);
    }

    
}
