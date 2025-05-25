<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfController extends Controller
{
    public function show($id)
    {
        $invoice = Invoice::with(['owner', 'lote', 'items'])->findOrFail($id);
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));
        return $pdf->download('factura_'.$invoice->public_identifier.'.pdf');
    }
}
