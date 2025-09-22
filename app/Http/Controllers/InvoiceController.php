<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use Illuminate\Http\Request;

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
}
