<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
     public function summary()
    {
        $totalSales = Order::sum('total');
        $totalItemsSold = OrderItem::sum('quantity');
        $totalCollections = Collection::count();
        $totalInvoices = Invoice::count();

        // Collection sales per month (SQLite compatible)
        $collectionSales = OrderItem::select(
            DB::raw("strftime('%m', created_at) as month"),
            DB::raw("SUM(price * quantity) as total")
        )
            ->groupBy(DB::raw("strftime('%m', created_at)"))
            ->orderBy("month")
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::create()->month((int)$item->month)->format('M'),
                    'total' => $item->total,
                ];
            });

        // Sales comparison (SQLite compatible)
        $thisMonth = OrderItem::whereRaw("strftime('%m', created_at) = ?", [now()->format('m')])
            ->sum(DB::raw('price * quantity'));

        $lastMonth = OrderItem::whereRaw("strftime('%m', created_at) = ?", [now()->subMonth()->format('m')])
            ->sum(DB::raw('price * quantity'));

        return response()->json([
            'totalSales' => $totalSales,
            'totalItemsSold' => $totalItemsSold,
            'totalCollections' => $totalCollections,
            'totalInvoices' => $totalInvoices,
            'collectionSales' => $collectionSales,
            'salesComparison' => [
                'lastMonth' => $lastMonth,
                'thisMonth' => $thisMonth,
            ],
        ]);
    }
}
