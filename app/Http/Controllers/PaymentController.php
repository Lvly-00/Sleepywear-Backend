<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function storePayment(Request $request, $orderId)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:cash,gcash,bank_transfer,paypal',
            'payment_image' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,failed,refunded'
        ]);

        $order = Order::findOrFail($orderId);
        $payment = $order->payment()->create($data);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment
        ], 201);
    }
}
