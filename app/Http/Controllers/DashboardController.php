<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {

        try {
            $grossIncome = Payment::where('payment_status', 'Paid')->sum('total');
            $additionalFees = Payment::where('payment_status', 'Paid')->sum('additional_fee');
            $netIncome = $grossIncome - $additionalFees;

            $totalItemsSold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->where('order_items.status', 'Sold Out')
                ->sum('order_items.quantity');

            $totalCustomers = Order::join('payments', 'orders.id', '=', 'payments.order_id')
                ->where('payments.payment_status', 'Paid')
                ->whereNotNull('orders.customer_id')
                ->distinct()
                ->count('orders.customer_id');

            $collectionSales = Collection::select('collections.id', 'collections.name')
                ->leftJoin('items', 'collections.id', '=', 'items.collection_id')
                ->leftJoin('order_items', 'items.id', '=', 'order_items.item_id')
                ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
                ->where('payments.payment_status', 'Paid')
                ->where('order_items.status', 'Sold Out')
                ->groupBy('collections.id', 'collections.name')
                ->selectRaw('collections.name as collection_name, COALESCE(SUM(order_items.price * order_items.quantity), 0) as total_sales')
                ->get()
                ->map(function ($collection) {
                    return [
                        'collection_name' => $collection->collection_name,
                        'total_sales' => round($collection->total_sales),
                    ];
                });

            \Log::info('Dashboard summary data:', [
                'totalCustomers' => $totalCustomers,
                'grossIncome' => $grossIncome,
                'netIncome' => $netIncome,
            ]);

            return response()->json([
                'grossIncome' => round($grossIncome),
                'netIncome' => round($netIncome),
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'collectionSales' => $collectionSales,
            ]);

            return response()->json(['message' => 'No data returned, test']);
        } catch (\Exception $e) {
            Log::error('Dashboard summary error: '.$e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
