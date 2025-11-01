<?php

namespace App\Http\Controllers;

use App\Models\DashboardMetric;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function storePayment(Request $request, $orderId)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:Cash,GCash,Paypal,Bank',
            'total' => 'required|numeric|min:0',
            'payment_status' => 'required|in:Unpaid,Paid',
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Load order with relations
            $order = Order::with('orderItems.item.collection', 'invoice', 'payment')->findOrFail($orderId);

            // Create or update payment
            $payment = Payment::firstOrCreate(['order_id' => $order->id]);
            $payment->update([
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
                'total' => $data['total'],
                'payment_date' => $data['payment_status'] === 'Paid' ? now() : null,
                'additional_fee' => $data['additional_fee'] ?? 0,
            ]);

            // Mark items as sold if paid
            if ($payment->payment_status === 'Paid') {
                $order->orderItems()->update(['status' => 'Sold Out']);
                $this->updateDashboardMetrics($order, $data['total']);
            }

            // Update invoice total and status
            if ($order->invoice) {
                $grandTotal = $order->total + ($payment->additional_fee ?? 0);
                $order->invoice->update([
                    'status' => $payment->payment_status === 'Paid' ? 'Paid' : 'Draft',
                    'total' => $grandTotal,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully',
                'order' => $order->fresh('invoice', 'orderItems.item.collection', 'payment'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Payment save failed: '.$e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to save payment',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    protected function updateDashboardMetrics(Order $order, $amount)
    {
        $date = Carbon::now()->format('Y-m-d');
        $metric = DashboardMetric::firstOrNew(['date' => $date]);

        // Fetch all paid orders for the date
        $paidOrders = Order::with('orderItems.item.collection')
            ->whereDate('created_at', $date)
            ->whereHas('payment', fn ($q) => $q->where('payment_status', 'Paid'))
            ->get();

        // Recompute totals from scratch
        $grossIncome = 0;
        $netIncome = 0;
        $totalItemsSold = 0;
        $totalInvoices = 0;
        $collectionSales = [];

        foreach ($paidOrders as $paidOrder) {
            $orderAmount = $paidOrder->payment->total ?? 0;
            $grossIncome += $orderAmount;
            $netIncome += $orderAmount - $paidOrder->totalCapital();
            $totalItemsSold += $paidOrder->orderItems->sum('quantity');
            $totalInvoices += 1;

            foreach ($paidOrder->orderItems as $orderItem) {
                $collectionName = $orderItem->item->collection->name;
                $collectionSales[$collectionName] = ($collectionSales[$collectionName] ?? 0)
                    + ($orderItem->price * $orderItem->quantity);
            }
        }

        // Update dashboard metric
        $metric->gross_income = $grossIncome;
        $metric->net_income = $netIncome;
        $metric->total_items_sold = $totalItemsSold;
        $metric->total_invoices = $totalInvoices;
        $metric->collection_sales = $collectionSales;

        $metric->save();

        // Mark order as dashboard updated
        $order->dashboard_updated = true;
        $order->previous_amount = $amount;
        $order->save();
    }
}
