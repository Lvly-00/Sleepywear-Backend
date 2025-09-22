<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('orders.items')->orderBy('created_at', 'desc')->paginate(25);
        return response()->json($invoices);
    }
}
