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

    /**
     * Construye un objeto de factura simulada a partir de una configuración y un lote.
     * Si el lote está en un grupo personalizado, usa los items del grupo; si no, usa los items globales.
     */
    protected function buildDraftInvoice($invoiceConfig, $lote)
    {
        $owner = $lote->owner;
        $config = $invoiceConfig->config;
        $period = $invoiceConfig->period ?? ($config['period'] ?? now());
        $due_date = $invoiceConfig->expiration_date ?? ($config['expiration_date'] ?? now()->addDays(10));
        $items = collect();
        $observations = null;
        // Buscar si el lote está en algún grupo personalizado
        $custom = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
        $foundGroup = false;
        if ($custom && isset($custom['data']['groups'])) {
            foreach ($custom['data']['groups'] as $grupo) {
                if (in_array($lote->id, $grupo['lotes_id'] ?? [])) {
                    $items = collect($grupo['items'] ?? []);
                    $observations = $grupo['observations'] ?? null;
                    $foundGroup = true;
                    break;
                }
            }
        }
        // Si no está en grupo, usar items globales
        if (!$foundGroup) {
            $global = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'items_invoice');
            if ($global && isset($global['data']['items'])) {
                $items = collect($global['data']['items']);
            }
        }
        // Normalizar items a objetos para la vista
        $items = $items->map(function($item) {
            return (object) $item;
        });
        $total = $items->sum(fn($item) => floatval($item->amount ?? 0));
        return (object) [
            'public_identifier' => 'BORRADOR-'.$lote->id,
            'owner' => $owner,
            'lote' => $lote,
            'period' => $period,
            'due_date' => $due_date,
            'status' => 'borrador',
            'items' => $items,
            'total' => $total,
            'observations' => $observations,
        ];
    }

    public function preview(Request $request, $key)
    {
        $preview = session($key);
        if (!$preview) {
            abort(404, 'No hay datos de borrador.');
        }
        $invoiceConfig = \App\Models\InvoiceConfig::findOrFail($preview['invoice_config_id']);
        $lotes = \App\Models\Lote::with('owner')->whereIn('id', $preview['lotes_id'])->get();
        // Solo uno por la UI
        $lote = $lotes->first();
        if (!$lote) abort(404, 'No hay datos de lote.');
        $invoice = $this->buildDraftInvoice($invoiceConfig, $lote);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice'));
        return $pdf->stream('borrador_factura_'.$invoice->public_identifier.'.pdf');
    }
}
