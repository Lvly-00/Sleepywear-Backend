<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('orders.items')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('orders.items.customers', 'orders');
        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_ref' => 'required|string|unique:invoices,invoice_ref',
            'customer_name' => 'nullable|string',
            'status' => 'in:draft,paid',
            'total' => 'numeric|min:0',
            'additional_fee' => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::create($data);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * ✅ Update invoice total and status dynamically
     * Adds additional_fee only when all orders are paid.
     */
    public function updateInvoiceStatus($invoiceId)
    {
        $invoice = Invoice::with(['orders.items'])->findOrFail($invoiceId);

        // Check if all orders are paid
        $allPaid = $invoice->orders->every(fn($order) => $order->payment_status === 'paid');

        // Sum total paid from all orders
        $orderTotal = $invoice->orders->sum('total_paid');

        // Include additional fee only if all orders are paid
        $grandTotal = $allPaid
            ? $orderTotal + ($invoice->additional_fee ?? 0)
            : $orderTotal;

        // Update invoice
        $invoice->update([
            'status' => $allPaid ? 'paid' : 'draft',
            'total' => $grandTotal,
        ]);

        return response()->json([
            'message' => 'Invoice status and total updated successfully',
            'invoice' => $invoice->fresh('orders.items'),
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        DB::beginTransaction();
        try {
            foreach ($invoice->orders as $order) {
                foreach ($order->items as $item) {
                    $item->customers()->delete();
                }
                $order->delete();
            }

            $invoice->delete();
            DB::commit();

            return response()->json(['message' => 'Invoice and associated orders deleted successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to delete invoice',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
