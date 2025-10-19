<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        // Total gross income = sum of paid invoices
        $grossIncome = Order::where('payment_status', 'paid')->sum('total_paid');

        // Total capital from collections
        $totalCapital = Collection::sum('capital');

        $netIncome = $grossIncome - $totalCapital;

        $totalItemsSold = OrderItem::sum('quantity');
        $totalInvoices = Order::count();
        $totalCollections = Collection::count();

        // Daily sales per collection
        $collectionSales = Collection::with(['items' => function($query) {
            $query->select('id', 'collection_id');
        }])->get()->map(function ($collection) {
            // Sum sales per day for this collection
            $dailySales = OrderItem::whereIn('item_id', $collection->items->pluck('id'))
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'paid')
                ->select(
                    DB::raw("DATE(orders.order_date) as day"),
                    DB::raw("SUM(order_items.price * order_items.quantity) as total")
                )
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            return [
                'collection_name' => $collection->name,
                'dailySales' => $dailySales->map(function ($sale) {
                    return [
                        'date' => Carbon::parse($sale->day)->format('Y-m-d'),
                        'total' => round($sale->total),
                    ];
                }),
            ];
        });

        return response()->json([
            'grossIncome' => $grossIncome,
            'netIncome' => $netIncome,
            'totalItemsSold' => $totalItemsSold,
            'totalCollections' => $totalCollections,
            'totalInvoices' => $totalInvoices,
            'collectionSales' => $collectionSales,
        ]);
    }
}
