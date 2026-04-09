<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            $paidOrdersQuery = Order::where('user_id', $userId)
                ->whereHas('payment', fn ($q) => $q->where('payment_status', 'Paid'));

            $totalRevenue = (clone $paidOrdersQuery)->sum('total');

            $totalItemsSold = DB::table('order_items')
                ->where('user_id', $userId)
                ->sum('quantity');

            $totalCustomers = (clone $paidOrdersQuery)->distinct('customer_id')->count('customer_id');

            $netIncome = Collection::where('user_id', $userId)->sum('capital');

            $paidOrdersCount = (clone $paidOrdersQuery)->count();
            $avgOrderValue = $paidOrdersCount > 0 ? ($totalRevenue / $paidOrdersCount) : 0;

            // Monthly Sales
            $monthlySales = (clone $paidOrdersQuery)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('total');

            $goalReached = $monthlySales > 0 ? round(($monthlySales / 50000) * 100) : 0;

            $lowStock = Item::whereHas('collection', fn ($q) => $q->where('user_id', $userId))
                ->where('status', 'Available')
                ->count();
            $stockHealth = $lowStock < 5 && $lowStock > 0 ? "Restock ($lowStock)" : 'All Good';

            $collections = Collection::where('user_id', $userId)->select('id', 'name')->get();
            $collectionNames = $collections->pluck('name')->toArray();

            $dailySales = [];
            for ($day = 1; $day <= $now->daysInMonth; $day++) {
                $row = ['date' => $day];
                foreach ($collectionNames as $name) {
                    $row[$name] = 0;
                }
                $dailySales[] = $row;
            }

            $itemsSoldThisMonth = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('items', 'order_items.item_id', '=', 'items.id')
                ->join('collections', 'items.collection_id', '=', 'collections.id')
                ->join('payments', 'orders.id', '=', 'payments.order_id')
                ->where('orders.user_id', $userId)
                ->where('payments.payment_status', 'Paid')
                ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
                ->select(
                    DB::raw('EXTRACT(DAY FROM orders.created_at) as day'),
                    'collections.name as collection_name',
                    DB::raw('SUM(order_items.price * order_items.quantity) as total_price')
                )
                ->groupBy('day', 'collections.name')
                ->get();

            foreach ($itemsSoldThisMonth as $sale) {
                $dayIdx = (int) $sale->day - 1;
                if (isset($dailySales[$dayIdx])) {
                    $dailySales[$dayIdx][$sale->collection_name] = (float) $sale->total_price;
                }
            }

            // 5. DETAILED ORDERS
            $detailedOrders = (clone $paidOrdersQuery)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->with(['items'])
                ->limit(10)
                ->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => str_pad($o->order_number, 4, '0', STR_PAD_LEFT),
                    'day' => (int) $o->created_at->format('d'),
                    'total' => (float) $o->total,
                    'customer' => trim($o->first_name.' '.$o->last_name),
                    'items' => $o->items->count(),
                ]);

            return response()->json([
                'grossIncome' => round($totalRevenue),
                'netIncome' => round($netIncome),
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'avgOrderValue' => round($avgOrderValue),
                'goalReached' => $goalReached,
                'stockHealth' => $stockHealth,
                'dailySales' => $dailySales,
                'collectionSales' => $collectionNames,
                'detailedOrders' => $detailedOrders,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard Error: '.$e->getMessage());

            return response()->json(['message' => 'Server Error', 'details' => $e->getMessage()], 500);
        }
    }
}
