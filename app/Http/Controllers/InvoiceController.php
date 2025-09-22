<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('orders.items')->orderBy('created_at', 'desc')->paginate(25);
        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('orders.items.customers', 'orders');
        return response()->json($invoice);
    }

    public function download(Invoice $invoice)
    {
        $invoice->load('orders.items');
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        return $pdf->download("invoice-{$invoice->invoice_ref}.pdf");
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

            return response()->json(['message' => 'Invoice and associated orders deleted']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete invoice', 'details' => $e->getMessage()], 500);
        }
    }
}
