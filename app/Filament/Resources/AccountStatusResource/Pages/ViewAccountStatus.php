<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use App\Filament\Resources\AccountStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;

class ViewAccountStatus extends ViewRecord
{
    protected static string $resource = AccountStatusResource::class;

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         Section::make('Movimientos')
    //             ->description(function ($record) {
    //                 $ownerId = $record->owner_id;
    //                 $loteId = $record->lote_id ?? null;
    //                 $invoices = \App\Models\Invoice::where('owner_id', $ownerId)
    //                     ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
    //                     ->get()
    //                     ->map(function ($inv) {
    //                         return [
    //                             'fecha' => $inv->period,
    //                             'tipo' => 'Factura',
    //                             'descripcion' => $inv->public_identifier . ' ' . ($inv->items->pluck('description')->join(' + ') ?? ''),
    //                             'monto' => $inv->total,
    //                         ];
    //                     });
    //                 $payments = \App\Models\Payment::where('owner_id', $ownerId)
    //                     ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
    //                     ->get()
    //                     ->map(function ($pay) {
    //                         return [
    //                             'fecha' => $pay->payment_date,
    //                             'tipo' => 'Pago',
    //                             'descripcion' => $pay->notes,
    //                             'monto' => $pay->amount,
    //                         ];
    //                     });
    //                 $all = $invoices->concat($payments)->sortBy('fecha')->values();
    //                 // Render HTML table
    //                 $html = '<div class="overflow-x-auto"><table class="min-w-full text-xs text-left border border-gray-200"><thead><tr>';
    //                 $html .= '<th class="px-2 py-1 border-b">Fecha</th><th class="px-2 py-1 border-b">Tipo</th><th class="px-2 py-1 border-b">Descripción</th><th class="px-2 py-1 border-b">Monto</th></tr></thead><tbody>';
    //                 foreach ($all as $mov) {
    //                     $color = $mov['tipo'] === 'Pago' ? 'green' : ($mov['monto'] < 0 ? 'red' : 'blue');
    //                     $html .= '<tr>';
    //                     $html .= '<td class="px-2 py-1 border-b">' . (\Carbon\Carbon::parse($mov['fecha'])->format('d/m/Y')) . '</td>';
    //                     $html .= '<td class="px-2 py-1 border-b">' . $mov['tipo'] . '</td>';
    //                     $html .= '<td class="px-2 py-1 border-b">' . e($mov['descripcion']) . '</td>';
    //                     $html .= '<td class="px-2 py-1 border-b font-bold" style="color:' . $color . '">' . number_format($mov['monto'], 2, ',', '.') . '</td>';
    //                     $html .= '</tr>';
    //                 }
    //                 if ($all->isEmpty()) {
    //                     $html .= '<tr><td colspan="4" class="text-center text-gray-400 py-2">Sin movimientos</td></tr>';
    //                 }
    //                 $html .= '</tbody></table></div>';
    //                 return $html;
    //             })
    //             ->columns(1),
    //     ];
    // }
}
