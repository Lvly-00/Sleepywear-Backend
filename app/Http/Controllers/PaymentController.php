<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
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
            ]);

            // Mark items as sold if paid
            if ($payment->payment_status === 'Paid') {
                $order->orderItems()->update(['status' => 'Sold Out']);
            }

            // Update invoice total and status
            if ($order->invoice) {
                $order->invoice->update([
                    'status' => $payment->payment_status === 'Paid' ? 'Paid' : 'Draft',
                    'total' => $order->total, // only base total now
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
}
