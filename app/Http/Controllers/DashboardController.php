<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Customer;
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
            $now = Carbon::now();
            $today = Carbon::today()->toDateString();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

            // 1. PERFORMANCE INDICATORS (Global Today)
            $pi = [
                'orders' => Order::where('user_id', $userId)->whereDate('created_at', $today)->count(),
                'leads' => Customer::where('user_id', $userId)->whereDate('created_at', $today)->count(),
                'invoices' => Order::where('user_id', $userId)->whereDate('created_at', $today)->count(),
            ];

            // 2. CRITICAL RISKS (KRI) - Grouped by Collection
            $collections = Collection::where('user_id', $userId)->get(['id', 'name']);
            $kris = [];

            // Helper for "All Collections" option
            $allUnpaid = Order::where('user_id', $userId)
                ->whereHas('payment', fn ($q) => $q->where('payment_status', '!=', 'Paid'));

            $kris['all'] = [
                'id' => 'all',
                'name' => 'All Collections',
                'receivables' => (float) $allUnpaid->sum('total'),
                'unpaid_orders' => $allUnpaid->count(),
                'dead_stock' => Item::whereHas('collection', fn ($q) => $q->where('user_id', $userId))->where('status', 'Available')->count(),
            ];

            foreach ($collections as $col) {
                $unpaidInCol = Order::where('orders.user_id', $userId)
                    ->whereHas('payment', fn ($q) => $q->where('payment_status', '!=', 'Paid'))
                    ->whereHas('items.item', fn ($q) => $q->where('collection_id', $col->id));

                $kris[$col->id] = [
                    'id' => $col->id,
                    'name' => $col->name,
                    'receivables' => (float) $unpaidInCol->sum('total'),
                    'unpaid_orders' => $unpaidInCol->count(),
                    'dead_stock' => Item::where('collection_id', $col->id)->where('status', 'Available')->count(),
                ];
            }

            // 3. REVENUE & CHART LOGIC (Existing Payment Date Logic)
            $paidOrdersQuery = Order::where('orders.user_id', $userId)
                ->join('payments', 'orders.id', '=', 'payments.order_id')
                ->where('payments.payment_status', 'Paid')
                ->with(['items.item.collection', 'payment']);

            $totalRevenue = (clone $paidOrdersQuery)->sum('orders.total');
            $monthlyPaidOrders = (clone $paidOrdersQuery)
                ->whereBetween('payments.payment_date', [$startOfMonth, $endOfMonth])
                ->select('orders.*')
                ->get();

            $totalItemsSold = OrderItem::where('user_id', $userId)
                ->whereIn('order_id', (clone $paidOrdersQuery)->pluck('orders.id'))
                ->sum('quantity');

            $totalCustomers = (clone $paidOrdersQuery)->distinct('orders.customer_id')->count('orders.customer_id');
            $totalCapital = Collection::where('user_id', $userId)->sum('capital');

            // Daily Sales Mapping
            $colNames = $collections->pluck('name')->toArray();
            $dailySales = [];
            for ($day = 1; $day <= $now->daysInMonth; $day++) {
                $row = ['date' => $day];
                foreach ($colNames as $name) {
                    $row[$name] = 0;
                }
                $dailySales[$day] = $row;
            }

            foreach ($monthlyPaidOrders as $order) {
                $day = (int) Carbon::parse($order->payment->payment_date)->format('j');
                foreach ($order->items as $orderItem) {
                    $cName = $orderItem->item->collection->name ?? null;
                    if ($cName && in_array($cName, $colNames)) {
                        $dailySales[$day][$cName] += (float) ($orderItem->price * $orderItem->quantity);
                    }
                }
            }

            return response()->json([
                'pi' => $pi,
                'kris' => $kris,
                'collections' => $collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
                'grossIncome' => round((float) $totalRevenue),
                'netIncome' => round((float) ($totalRevenue - $totalCapital)),
                'totalItemsSold' => (int) $totalItemsSold,
                'totalCustomers' => (int) $totalCustomers,
                'dailySales' => array_values($dailySales),
                'collectionSales' => $colNames,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard Error: '.$e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
