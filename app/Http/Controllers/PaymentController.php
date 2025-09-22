<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function storePayment(Request $request, $orderId)
    {
        $data = $request->validate([
            'payment_method' => 'required|in:Cash,GCash',
            'payment_image' => 'nullable|image|max:2048',
            'total_paid' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
        ]);

        $order = Order::findOrFail($orderId);

        // Handle image upload
        if ($request->hasFile('payment_image')) {
            $data['payment_image'] = $request->file('payment_image')->store('payments', 'public');
        }

        // Update order directly
        $order->update([
            'payment_method' => $data['payment_method'],
            'payment_image' => $data['payment_image'] ?? null,
            'payment_status' => $data['payment_status'],
            'total_paid' => $data['total_paid'],
        ]);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'order' => $order,
        ], 201);
    }
}
