<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionSalesSummary;
use App\Models\Item;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {
        try {
            $userId = auth()->id();
            $now = Carbon::now();

            // 1. ORIGINAL TOTALS LOGIC
            $totalRevenue = CollectionSalesSummary::where('user_id', $userId)->sum('total_sales');
            $totalItemsSold = CollectionSalesSummary::where('user_id', $userId)->sum('total_items_sold');
            $totalCustomers = Order::where('user_id', $userId)
                ->whereHas('payment', fn ($q) => $q->where('payment_status', 'Paid'))
                ->distinct('customer_id')->count('customer_id');

            // 2. ORIGINAL NET INCOME (CAPITAL) LOGIC
            $netIncome = CollectionSalesSummary::where('user_id', $userId)
                ->select('collection_id', 'collection_capital')
                ->groupBy('collection_id', 'collection_capital')
                ->get()->sum('collection_capital');

            // 3. ADDITIONAL METRICS FOR DESIGN
            $paidOrders = Order::where('user_id', $userId)
                ->whereHas('payment', fn ($q) => $q->where('payment_status', 'Paid'))
                ->with(['orderItems.item.collection', 'customer']);

            $paidOrdersCount = (clone $paidOrders)->count();
            $avgOrderValue = $paidOrdersCount > 0 ? ($totalRevenue / $paidOrdersCount) : 0;

            // Monthly Goal (Example target 50k)
            $monthlySales = (clone $paidOrders)->whereMonth('created_at', $now->month)->get()
                ->sum(fn ($o) => $o->orderItems->sum(fn ($i) => $i->price * $i->quantity));
            $goalReached = round(($monthlySales / 50000) * 100);

            // Stock Health
            $lowStock = Item::whereHas('collection', fn ($q) => $q->where('user_id', $userId))
                ->where('stock', '<', 5)->count();
            $stockHealth = $lowStock > 0 ? "Restock ($lowStock)" : 'All Good';

            // 4. CHART DATA (DAILY SALES)
            $collections = Collection::where('user_id', $userId)->pluck('name')->toArray();
            $dailySales = [];
            for ($day = 1; $day <= $now->daysInMonth; $day++) {
                $row = ['date' => $day];
                foreach ($collections as $c) {
                    $row[$c] = 0;
                }
                $dailySales[] = $row;
            }

            $currentMonthOrders = (clone $paidOrders)->whereMonth('created_at', $now->month)->get();
            foreach ($currentMonthOrders as $order) {
                $dayIdx = (int) $order->created_at->format('d') - 1;
                foreach ($order->orderItems as $item) {
                    $name = $item->item->collection->name ?? null;
                    if ($name && in_array($name, $collections)) {
                        $dailySales[$dayIdx][$name] += $item->price * $item->quantity;
                    }
                }
            }

            // 5. DETAILED ORDERS (For Chart Clicks)
            $detailedOrders = $currentMonthOrders->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number ?? '#'.$o->id,
                'day' => (int) $o->created_at->format('d'),
                'total' => $o->orderItems->sum(fn ($i) => $i->price * $i->quantity),
                'customer' => $o->customer->name ?? 'Guest',
                'items' => $o->orderItems->sum('quantity'),
                'collections' => $o->orderItems->map(fn ($i) => $i->item->collection->name ?? 'Items')->unique()->values(),
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
                'collectionSales' => $collections,
                'detailedOrders' => $detailedOrders,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
