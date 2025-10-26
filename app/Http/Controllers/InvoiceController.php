<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('order.items')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'order.items'
        ]);

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

        $invoice = Invoice::create($data);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ], 201);
    }

    public function updateInvoiceStatus($invoiceId)
    {
        $invoice = Invoice::with('order')->findOrFail($invoiceId);
        $order = $invoice->order;

        if (!$order) {
            throw new \Exception('No order linked to this invoice.');
        }

        $isPaid = $order->payment_status === 'Paid';
        $totalPaid = $order->total_paid ?? 0;
        $grandTotal = $order->total + ($invoice->additional_fee ?? 0);

        $invoice->update([
            'status' => $isPaid ? 'Paid' : 'Draft',
            'total' => $grandTotal,
        ]);

        return response()->json([
            'message' => 'Invoice status and total updated successfully',
            'invoice' => $invoice->fresh('order.items'),
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
                    'total' => 0
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
