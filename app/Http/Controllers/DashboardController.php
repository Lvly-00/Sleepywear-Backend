<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function summary()
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            // 1. BASE QUERY FOR PAID ORDERS
            // We use this for revenue, items sold, and the chart.
            // Using 'items.item.collection' - ensure these relations exist in your Models
            $paidOrders = Order::where('user_id', $userId)
                ->whereHas('payment', function ($q) {
                    $q->where('payment_status', 'Paid');
                })
                ->with(['items.item.collection'])
                ->get();

            // 2. TOTALS LOGIC (Database Agnostic)
            $totalRevenue = $paidOrders->sum('total');

            // Total Items Sold (Summing from order_items table for accuracy)
            $totalItemsSold = OrderItem::where('user_id', $userId)
                ->whereIn('order_id', $paidOrders->pluck('id'))
                ->sum('quantity');

            $totalCustomers = $paidOrders->pluck('customer_id')->unique()->count();

            // 3. NET INCOME (Sum of Capital from Collections)
            $totalCapital = Collection::where('user_id', $userId)->sum('capital');

            // 4. ADDITIONAL METRICS
            $avgOrderValue = $paidOrders->count() > 0 ? ($totalRevenue / $paidOrders->count()) : 0;

            // Monthly Sales (Filter the already fetched collection for speed)
            $monthlySales = $paidOrders->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total');
            $goalReached = $monthlySales > 0 ? round(($monthlySales / 50000) * 100) : 0;

            // Stock Health
            $lowStockCount = Item::whereHas('collection', fn ($q) => $q->where('user_id', $userId))
                ->where('status', 'Available')
                ->count();
            $stockHealth = ($lowStockCount < 5 && $lowStockCount > 0) ? "Restock ($lowStockCount)" : 'All Good';

            // 5. CHART DATA (Universal logic using PHP instead of SQL Functions)
            $collections = Collection::where('user_id', $userId)->pluck('name')->toArray();

            $dailySales = [];
            $daysInMonth = $now->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $row = ['date' => $day];
                foreach ($collections as $name) {
                    $row[$name] = 0;
                }
                $dailySales[$day] = $row;
            }

            // Process sales for the current month
            $currentMonthOrders = $paidOrders->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

            foreach ($currentMonthOrders as $order) {
                $day = (int) $order->created_at->format('j'); // 'j' is day of month without leading zeros

                foreach ($order->items as $orderItem) {
                    $collectionName = $orderItem->item->collection->name ?? null;

                    if ($collectionName && in_array($collectionName, $collections)) {
                        $amount = $orderItem->price * $orderItem->quantity;
                        $dailySales[$day][$collectionName] += (float) $amount;
                    }
                }
            }

            // 6. DETAILED ORDERS (For Chart Clicks / Recent Activity)
            $detailedOrders = $currentMonthOrders->take(10)->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => str_pad($o->order_number, 4, '0', STR_PAD_LEFT),
                'day' => (int) $o->created_at->format('j'),
                'total' => (float) $o->total,
                'customer' => trim($o->first_name.' '.$o->last_name),
                'items' => $o->items->count(),
                'collections' => $o->items->map(fn ($i) => $i->item->collection->name ?? 'Uncategorized')->unique()->values(),
            ])->values();

            return response()->json([
                'grossIncome' => round((float) $totalRevenue),
                'netIncome' => round((float) $totalCapital), // Total invested capital
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'avgOrderValue' => round((float) $avgOrderValue),
                'goalReached' => (int) $goalReached,
                'stockHealth' => $stockHealth,
                'dailySales' => array_values($dailySales), // Reset keys for JSON array
                'collectionSales' => $collections,
                'detailedOrders' => $detailedOrders,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard Error: '.$e->getMessage());

            return response()->json([
                'message' => 'Server Error',
                'debug' => $e->getMessage(), // Remove this line in final production
            ], 500);
        }
    }
}
