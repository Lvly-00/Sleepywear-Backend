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
        ]);

        $order = Order::findOrFail($data['order_id']);
        $grandTotal = $order->total;

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'status' => $data['status'] ?? 'Draft',
            'total' => $grandTotal,
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

        $invoice->update([
            'status' => $isPaid ? 'Paid' : 'Draft',
            'total' => $order->total,
        ]);

        return response()->json([
            'message' => 'Invoice status and total updated successfully',
            'invoice' => $invoice->fresh('order.items', 'order.payment'),
        ]);
    }

    public function destroyInvoice($invoiceId)
    {
        $invoice = Invoice::with('order.payment', 'order.items')->findOrFail($invoiceId);

        DB::beginTransaction();
        try {
            $order = $invoice->order;

            if ($order) {
                $isPaid = $order->payment && $order->payment->payment_status === 'Paid';

                if ($isPaid) {
                    // Paid order: Do NOT delete invoice or order
                    return response()->json([
                        'message' => 'Cannot delete invoice linked to a paid order.',
                    ], 400);
                }

                // Unpaid order: revert items and delete order + invoice
                foreach ($order->items as $item) {
                    $item->update(['status' => 'Available']);
                }

                $order->delete();
            }

            $invoice->delete();

            DB::commit();

            return response()->json(['message' => 'Invoice and linked unpaid order deleted successfully']);
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
        $order = Order::with('items', 'payment', 'invoice')->findOrFail($orderId);

        DB::beginTransaction();
        try {
            $isPaid = $order->payment && $order->payment->payment_status === 'Paid';

            // Revert item status for unpaid only
            if (! $isPaid) {
                foreach ($order->items as $item) {
                    $item->update(['status' => 'Available']);
                }
            }

            // Delete payment always if exists
            if ($order->payment) {
                $order->payment->delete();
            }

            if ($isPaid) {
                // Paid order: keep invoice, just delete order and payment
                $order->delete();

                return response()->json(['message' => 'Paid order deleted; payment removed, invoice retained']);
            } else {
                // Unpaid order: delete order and invoice, reset invoice if exists
                $invoice = $order->invoice;

                $order->delete();

                if ($invoice) {
                    $invoice->delete();
                }

                return response()->json(['message' => 'Unpaid order and invoice deleted successfully']);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
