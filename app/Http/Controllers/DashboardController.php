<?php

namespace App\Http\Controllers;

use App\Models\DashboardMetric;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary()
    {
        $today = Carbon::now()->format('Y-m-d');

        // Get today's dashboard metric as reference
        $metric = DashboardMetric::latest('date')->first();

        // Fallback to 0 if no metric exists
        $grossIncome = $metric->gross_income ?? 0;
        $netIncome = $metric->net_income ?? 0;
        $totalItemsSold = $metric->total_items_sold ?? 0;
        $totalInvoices = $metric->total_invoices ?? 0;

        // For collections, aggregate sales across all metrics
        $collectionSalesData = DashboardMetric::orderBy('date')
            ->get()
            ->pluck('collection_sales');

        $collectionTotals = [];

        foreach ($collectionSalesData as $dailySales) {
            foreach ($dailySales as $collectionName => $total) {
                $collectionTotals[$collectionName][] = [
                    'date' => $dailySales['date'] ?? Carbon::now()->format('Y-m-d'),
                    'total' => $total,
                ];
            }
        }

        $collectionSales = collect($collectionTotals)->map(function ($sales, $collectionName) {
            return [
                'collection_name' => $collectionName,
                'dailySales' => collect($sales)->map(function ($s) {
                    return [
                        'date' => $s['date'],
                        'total' => round($s['total']),
                    ];
                }),
            ];
        })->values();

        return response()->json([
            'grossIncome' => $grossIncome,
            'netIncome' => $netIncome,
            'totalItemsSold' => $totalItemsSold,
            'totalInvoices' => $totalInvoices,
            'collectionSales' => $collectionSales,
        ]);
    }
}
