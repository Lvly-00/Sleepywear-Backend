<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function storePayment(Request $request, $orderId)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:Cash,GCash,Paypal,Bank',
            'payment_image' => 'nullable|image|max:2048',
            'total_paid' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('invoice')->findOrFail($orderId);

            // Upload payment proof if provided
            if ($request->hasFile('payment_image')) {
                $data['payment_image'] = $request->file('payment_image')->store('payments', 'public');
            }

            // Update order
            $order->update([
                'payment_method' => $data['payment_method'],
                'payment_image' => $data['payment_image'] ?? null,
                'payment_status' => $data['payment_status'],
                'total_paid' => $data['total_paid'],
                'payment_date' => $data['payment_status'] === 'paid' ? now() : null,
            ]);

            // Update all order items status to "sold" if fully paid
            if ($data['payment_status'] === 'paid') {
                $order->orderItems()->update(['status' => 'sold']);
            }

            // Update invoice's additional fee if given
            if (isset($data['additional_fee'])) {
                $order->invoice->update([
                    'additional_fee' => $data['additional_fee']
                ]);
            }

            // Refresh invoice totals and status
            app(InvoiceController::class)->updateInvoiceStatus($order->invoice_id);

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully',
                'order' => $order->fresh('invoice'),
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
