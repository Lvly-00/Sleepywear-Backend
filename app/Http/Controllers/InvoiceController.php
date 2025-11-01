<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $invoice->load(['order.items']);

        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'in:Draft,Paid',
            'total' => 'numeric|min:0',
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        $order = Order::findOrFail($data['order_id']);
        $additionalFee = $data['additional_fee'] ?? 0;
        $grandTotal = $order->total + $additionalFee;

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'status' => $data['status'] ?? 'Draft',
            'total' => $grandTotal,
            'additional_fee' => $additionalFee,
        ]);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ], 201);
    }

    public function updateInvoiceStatus($invoiceId)
    {
        $invoice = Invoice::with('order.payment')->findOrFail($invoiceId);
        $order = $invoice->order;

        if (! $order) {
            throw new \Exception('No order linked to this invoice.');
        }

        $payment = $order->payment;
        $isPaid = $payment?->payment_status === 'Paid';
        $additionalFee = $order->payment?->additional_fee ?? 0;
        $grandTotal = $order->total + $additionalFee;

        $invoice->update([
            'status' => $isPaid ? 'Paid' : 'Draft',
            'total' => $order->total + $additionalFee,
            'additional_fee' => $additionalFee,
        ]);

        return response()->json([
            'message' => 'Invoice status, total, and additional fee updated successfully',
            'invoice' => $invoice->fresh('order.items', 'order.payment'),
        ]);
    }

    public function destroyInvoice($invoiceId)
    {
        $invoice = Invoice::with('order.items')->findOrFail($invoiceId);

        DB::beginTransaction();
        try {
            $order = $invoice->order;
            if ($order) {
                foreach ($order->items as $item) {
                    $item->update(['status' => 'Available']);
                }
                $order->delete();
            }

            $invoice->delete();

            DB::commit();

            return response()->json(['message' => 'Invoice and linked order deleted successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to delete invoice',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyOrder($orderId)
    {
        $order = Order::with('items', 'invoice')->findOrFail($orderId);

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                $item->update(['status' => 'Available']);
            }

            $invoice = $order->invoice;
            $order->delete();

            if ($invoice) {
                $invoice->update([
                    'status' => 'Draft',
                    'total' => 0,
                    'additional_fee' => 0,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Order deleted and invoice updated successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
