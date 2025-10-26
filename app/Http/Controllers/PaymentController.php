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
            'total_paid' => 'required|numeric|min:0',
            'payment_status' => 'required|in:Unpaid,Paid',
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('orderItems', 'invoice', 'payment')->findOrFail($orderId);

            // Create or update Payment record
            $payment = Payment::firstOrCreate(['order_id' => $order->id]);
            $payment->update([
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
                'total_paid' => $data['total_paid'],
                'payment_date' => $data['payment_status'] === 'Paid' ? now() : null,
                'additional_fee' => $data['additional_fee'] ?? 0,
            ]);

            // Mark order items as Sold Out if paid
            if ($payment->payment_status === 'Paid') {
                $order->orderItems()->update(['status' => 'Sold Out']);
            }

            // Update invoice totals and status if invoice exists
            if ($order->invoice) {
                $grandTotal = $order->total + ($payment->additional_fee ?? 0);
                $order->invoice->update([
                    'status' => $payment->payment_status === 'Paid' ? 'Paid' : 'Draft',
                    'total' => $grandTotal,
                ]);
            }

            DB::commit();

            // Return updated order with payment info
            return response()->json([
                'message' => 'Payment recorded successfully',
                'order' => $order->fresh('invoice', 'orderItems', 'payment'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to save payment',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
