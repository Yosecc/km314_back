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

    public function preview(Request $request, $key)
    {
        $preview = session($key);
        if (!$preview) {
            abort(404, 'No hay datos de borrador.');
        }
        $invoiceConfig = \App\Models\InvoiceConfig::findOrFail($preview['invoice_config_id']);
        $lotes = \App\Models\Lote::with('owner')->whereIn('id', $preview['lotes_id'])->get();
        // Simular una factura para cada lote seleccionado
        $facturas = [];
        foreach ($lotes as $lote) {
            $owner = $lote->owner;
            // Buscar items globales y personalizados para el lote
            $config = $invoiceConfig->config;
            $period = $invoiceConfig->period ?? ($config['period'] ?? now());
            $due_date = $invoiceConfig->expiration_date ?? ($config['expiration_date'] ?? now()->addDays(10));
            $items = collect();
            // Buscar items globales
            $global = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'items_invoice');
            if ($global && isset($global['data']['items'])) {
                $items = $items->merge($global['data']['items']);
            }
            // Buscar si el lote está en algún grupo personalizado
            $custom = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
            if ($custom && isset($custom['data']['groups'])) {
                foreach ($custom['data']['groups'] as $grupo) {
                    if (in_array($lote->id, $grupo['lotes_id'] ?? [])) {
                        $items = collect($grupo['items'] ?? []);
                        break;
                    }
                }
            }
            $total = $items->sum(fn($item) => floatval($item['amount'] ?? 0));
            $facturas[] = (object) [
                'public_identifier' => 'BORRADOR-'.$lote->id,
                'owner' => $owner,
                'lote' => $lote,
                'period' => $period,
                'due_date' => $due_date,
                'status' => 'borrador',
                'items' => $items,
                'total' => $total,
            ];
        }
        // Renderizar el PDF para el primer lote (o podrías mostrar todos)
        $invoice = $facturas[0] ?? null;
        if (!$invoice) abort(404, 'No hay datos de factura.');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice'));
        return $pdf->stream('borrador_factura_'.$invoice->public_identifier.'.pdf');
    }
}
