<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {
        try {
            //  Total Revenue = sum of all paid order items
            $totalRevenue = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->sum(\DB::raw('order_items.price * order_items.quantity'));

            // Cost of Goods Sold (COGS)
            $cogs = OrderItem::join('items', 'order_items.item_id', '=', 'items.id')
                ->join('collections', 'items.collection_id', '=', 'collections.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->sum(\DB::raw('(collections.capital / NULLIF(collections.qty, 0)) * order_items.quantity'));

            //  Gross Income = Total Revenue - COGS
            $grossIncome = $totalRevenue - $cogs;

            //  Business Expenses
        $totalCapital = Collection::sum('capital');

            //  Net Income = Gross Income - Business Expenses
            $netIncome = $totalCapital -  $grossIncome ;

            //  Total Items Sold
            $totalItemsSold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->sum('order_items.quantity');

            //  Total Customers
            $totalCustomers = Order::join('payments', 'orders.id', '=', 'payments.order_id')
                ->where('payments.payment_status', 'Paid')
                ->whereNotNull('orders.customer_id')
                ->distinct()
                ->count('orders.customer_id');

            //  Collection Sales
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

            // Log summary
            // Log::info('Dashboard summary data:', [
            //     'totalRevenue' => $totalRevenue,
            //     'grossIncome' => $grossIncome,
            //     'netIncome' => $netIncome,
            //     'totalCustomers' => $totalCustomers,
            // ]);

            return response()->json([
                'totalRevenue' => round($totalRevenue),
                'grossIncome' => round($grossIncome),
                'netIncome' => round($netIncome),
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'collectionSales' => $collectionSales,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard summary error: '.$e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
