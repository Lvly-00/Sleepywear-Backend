<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionSalesSummary;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {
        try {
            // ───────────────────────────────────────────────
            // TOTALS from CollectionSalesSummary table
            // ───────────────────────────────────────────────
            $totalRevenue   = CollectionSalesSummary::sum('total_sales');
            $totalItemsSold = CollectionSalesSummary::sum('total_items_sold');
            $totalCustomers = CollectionSalesSummary::sum('total_customers');
            $grossIncome    = $totalRevenue;

            // ───────────────────────────────────────────────
            // NET INCOME = sum of unique collection_capital
            // ───────────────────────────────────────────────
            $summaryCapital = CollectionSalesSummary::select('collection_id', 'collection_capital')
                ->groupBy('collection_id', 'collection_capital')
                ->get()
                ->sum('collection_capital');

            $netIncome = $summaryCapital;

                   // ───────────────────────────────────────────────
            // COLLECTION SALES using leftJoin (your logic)
            // ───────────────────────────────────────────────
            $collectionSales = Collection::leftJoin('items', 'collections.id', '=', 'items.collection_id')
                ->leftJoin('order_items', 'items.id', '=', 'order_items.item_id')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->groupBy('collections.id', 'collections.name')
                ->selectRaw('collections.name as collection_name, COALESCE(SUM(order_items.price * order_items.quantity), 0) as total_sales')
                ->get()
                ->map(function ($collection) {
                    return [
                        'collection_name' => $collection->collection_name,
                        'total_sales' => round($collection->total_sales),
                    ];
                });

            // ───────────────────────────────────────────────
            // DAILY SALES for chart
            // ───────────────────────────────────────────────
            $collections = Collection::pluck('name')->toArray();
            $monthDays = now()->daysInMonth;

            // Initialize daily sales array
            $dailySales = [];
            for ($day = 1; $day <= $monthDays; $day++) {
                $row = ['date' => $day];
                foreach ($collections as $collection) {
                    $row[$collection] = 0;
                }
                $dailySales[$day - 1] = $row; // 0-indexed
            }

            // Fetch orders with items and collections for current month
            $orders = Order::whereMonth('created_at', now()->month)
                ->whereHas('payment', function($q){
                    $q->where('payment_status', 'Paid');
                })
                ->with('orderItems.item.collection')
                ->get();

            // Aggregate sales per day per collection
            foreach ($orders as $order) {
                $dayIndex = (int) $order->created_at->format('d') - 1;
                foreach ($order->orderItems as $item) {
                    $collectionName = $item->item->collection->name ?? null;
                    if ($collectionName) {
                        $dailySales[$dayIndex][$collectionName] += $item->price * $item->quantity;
                    }
                }
            }

            // Convert to chart-ready array
            $chartData = array_values($dailySales);

            // ───────────────────────────────────────────────
            // RETURN JSON
            // ───────────────────────────────────────────────
            return response()->json([
                'totalRevenue'    => round($totalRevenue),
                'grossIncome'     => round($grossIncome),
                'netIncome'       => round($netIncome),
                'totalItemsSold'  => (int) $totalItemsSold,
                'totalCustomers'  => (int) $totalCustomers,
                'collectionSales' => $collectionSales,
                'dailySales'      => $chartData,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard summary error: '.$e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}



// <?php

// namespace App\Http\Controllers;

// use App\Models\Collection;
// use App\Models\Order;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\DB;

// class DashboardController extends Controller
// {
//     public function summary()
//     {
//         try {
//             // ───────────────────────────────────────────────
//             // TOTALS from orders/payments
//             // ───────────────────────────────────────────────
//             $totalRevenue = DB::table('orders')
//                 ->join('payments', 'orders.id', '=', 'payments.order_id')
//                 ->where('payments.payment_status', 'Paid')
//                 ->sum('orders.total');

//             $totalItemsSold = DB::table('order_items')
//                 ->join('orders', 'order_items.order_id', '=', 'orders.id')
//                 ->join('payments', 'payments.order_id', '=', 'orders.id')
//                 ->where('payments.payment_status', 'Paid')
//                 ->sum('order_items.quantity');

//             $totalCustomers = DB::table('orders')
//                 ->join('payments', 'payments.order_id', '=', 'orders.id')
//                 ->where('payments.payment_status', 'Paid')
//                 ->distinct('orders.customer_id')
//                 ->count('orders.customer_id');

//             $grossIncome = $totalRevenue;
//             $netIncome = $totalRevenue; // You can replace with capital logic if needed

//             // ───────────────────────────────────────────────
//             // COLLECTION SALES using leftJoin (your logic)
//             // ───────────────────────────────────────────────
//             $collectionSales = Collection::leftJoin('items', 'collections.id', '=', 'items.collection_id')
//                 ->leftJoin('order_items', 'items.id', '=', 'order_items.item_id')
//                 ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
//                 ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
//                 ->where('payments.payment_status', 'Paid')
//                 ->groupBy('collections.id', 'collections.name')
//                 ->selectRaw('collections.name as collection_name, COALESCE(SUM(order_items.price * order_items.quantity), 0) as total_sales')
//                 ->get()
//                 ->map(function ($collection) {
//                     return [
//                         'collection_name' => $collection->collection_name,
//                         'total_sales' => round($collection->total_sales),
//                     ];
//                 });

//             // ───────────────────────────────────────────────
//             // DAILY SALES for chart
//             // ───────────────────────────────────────────────
//             $collections = Collection::pluck('name')->toArray();
//             $monthDays = now()->daysInMonth;

//             // Initialize daily sales array
//             $dailySales = [];
//             for ($day = 1; $day <= $monthDays; $day++) {
//                 $row = ['date' => $day];
//                 foreach ($collections as $collection) {
//                     $row[$collection] = 0;
//                 }
//                 $dailySales[$day - 1] = $row; // 0-indexed
//             }

//             // Fetch orders with items and collections for current month
//             $orders = Order::whereMonth('created_at', now()->month)
//                 ->whereHas('payment', function($q){
//                     $q->where('payment_status', 'Paid');
//                 })
//                 ->with('orderItems.item.collection')
//                 ->get();

//             // Aggregate sales per day per collection
//             foreach ($orders as $order) {
//                 $dayIndex = (int) $order->created_at->format('d') - 1;
//                 foreach ($order->orderItems as $item) {
//                     $collectionName = $item->item->collection->name ?? null;
//                     if ($collectionName) {
//                         $dailySales[$dayIndex][$collectionName] += $item->price * $item->quantity;
//                     }
//                 }
//             }

//             // Convert to chart-ready array
//             $chartData = array_values($dailySales);

//             // ───────────────────────────────────────────────
//             // RETURN JSON
//             // ───────────────────────────────────────────────
//             return response()->json([
//                 'totalRevenue'    => round($totalRevenue),
//                 'grossIncome'     => round($grossIncome),
//                 'netIncome'       => round($netIncome),
//                 'totalItemsSold'  => (int) $totalItemsSold,
//                 'totalCustomers'  => (int) $totalCustomers,
//                 'collectionSales' => $collectionSales,
//                 'dailySales'      => $chartData,
//             ]);

//         } catch (\Exception $e) {
//             Log::error('Dashboard summary error: '.$e->getMessage());
//             return response()->json(['message' => 'Server Error'], 500);
//         }
//     }
// }
