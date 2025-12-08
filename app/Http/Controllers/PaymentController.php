<?php

namespace App\Http\Controllers;

use App\Models\CollectionSalesSummary;
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
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::with('orderItems.item.collection', 'invoice', 'payment')->findOrFail($orderId);

            $payment = Payment::firstOrCreate(['order_id' => $order->id]);

            // Check previous status to ensure we don't double count if clicked twice
            $wasAlreadyPaid = $payment->payment_status === 'Paid';

            $payment->update([
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'],
                'total' => $data['total'],
                'payment_date' => $data['payment_status'] === 'Paid' ? now() : null,
            ]);

            // ONLY execute revenue updates if status IS 'Paid' and WAS NOT 'Paid' before
            if ($payment->payment_status === 'Paid' && !$wasAlreadyPaid) {

                $today = now()->toDateString();

                foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->item) {
                        // UPDATED: Mark item as 'Sold Out' (This triggers revenue in CollectionController)
                        $orderItem->item->update(['status' => 'Sold Out']);

                        // Update Collection Sales Summary
                        $collectionId = $orderItem->item->collection_id ?? null;

                        if ($collectionId) {
                            $collectionName = $orderItem->item->collection->name ?? 'Unknown';
                            $collectionCapital = $orderItem->item->collection->capital ?? 0;

                            $summary = CollectionSalesSummary::firstOrNew([
                                'collection_id' => $collectionId,
                                'date' => $today,
                            ]);

                            $summary->collection_name = $collectionName;
                            $summary->collection_capital = $collectionCapital;
                            $summary->total_sales = ($summary->total_sales ?? 0) + ($orderItem->price * $orderItem->quantity);
                            $summary->total_items_sold = ($summary->total_items_sold ?? 0) + $orderItem->quantity;
                            $summary->total_customers = ($summary->total_customers ?? 0) + 1;
                            $summary->user_id = auth()->id();

                            $summary->save();
                        }
                    }
                }
            }

            // Optional: If payment is reverted to Unpaid, set items back to Reserved
            if ($payment->payment_status === 'Unpaid' && $wasAlreadyPaid) {
                 foreach ($order->orderItems as $orderItem) {
                    if ($orderItem->item) {
                        $orderItem->item->update(['status' => 'Reserved']);
                    }
                 }
                 // Note: You might want to decrement CollectionSalesSummary here if you want strict accuracy
            }

            if ($order->invoice) {
                $order->invoice->update([
                    'status' => $payment->payment_status === 'Paid' ? 'Paid' : 'Draft',
                    'total' => $order->total,
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
