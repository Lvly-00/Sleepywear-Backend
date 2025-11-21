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
            $userId = auth()->id();

            // ───────────────────────────────────────────────
            // TOTALS from CollectionSalesSummary table for this user only
            // ───────────────────────────────────────────────
            $totalRevenue = CollectionSalesSummary::where('user_id', $userId)->sum('total_sales');
            $totalItemsSold = CollectionSalesSummary::where('user_id', $userId)->sum('total_items_sold');
            $totalCustomers = CollectionSalesSummary::where('user_id', $userId)->sum('total_customers');
            $grossIncome = $totalRevenue;

            // ───────────────────────────────────────────────
            // NET INCOME = sum of unique collection_capital for this user
            // ───────────────────────────────────────────────
            $summaryCapital = CollectionSalesSummary::where('user_id', $userId)
                ->select('collection_id', 'collection_capital')
                ->groupBy('collection_id', 'collection_capital')
                ->get()
                ->sum('collection_capital');

            $netIncome = $summaryCapital;

            // ───────────────────────────────────────────────
            // COLLECTION SALES for this user only
            // ───────────────────────────────────────────────
            $collectionSales = Collection::leftJoin('items', 'collections.id', '=', 'items.collection_id')
                ->leftJoin('order_items', 'items.id', '=', 'order_items.item_id')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
                ->where('collections.user_id', $userId) // filter by user
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
            // DAILY SALES for chart (this user's data only)
            // ───────────────────────────────────────────────
            $collections = Collection::where('user_id', $userId)->pluck('name')->toArray();
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

            // Fetch orders for this user, current month, with paid payments
            $orders = Order::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereHas('payment', function ($q) {
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
            // RETURN JSON response scoped to this user only
            // ───────────────────────────────────────────────
            return response()->json([
                'totalRevenue' => round($totalRevenue),
                'grossIncome' => round($grossIncome),
                'netIncome' => round($netIncome),
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'collectionSales' => $collectionSales,
                'dailySales' => $chartData,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard summary error: '.$e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
