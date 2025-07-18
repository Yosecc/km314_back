<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class MovementsTableWidget extends Widget
{
    public ?object $record = null;

    protected static string $view = 'filament.widgets.movements-table-widget';

    public static function isVisible(): bool
{
    return false;
}

    public function getMovements(): array
    {
        if (!$this->record) return [];
        $ownerId = $this->record->owner_id;
        $loteId = $this->record->lote_id ?? null;
        $invoices = \App\Models\Invoice::where('owner_id', $ownerId)
            ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
            ->get()
            ->map(function ($inv) {
                return [
                    'fecha' => $inv->period,
                    'tipo' => 'Factura',
                    'descripcion' => $inv->public_identifier . ' ' . ($inv->items->pluck('description')->join(' + ') ?? ''),
                    'monto' => $inv->total,
                ];
            });
        $payments = \App\Models\Payment::where('owner_id', $ownerId)
            ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
            ->get()
            ->map(function ($pay) {
                return [
                    'fecha' => $pay->payment_date,
                    'tipo' => 'Pago',
                    'descripcion' => $pay->notes,
                    'monto' => $pay->amount,
                ];
            });
        $all = $invoices->concat($payments)->sortBy('fecha')->values();
        return $all->toArray();
    }

    public function render(): View
    {
        return view(static::$view, [
            'movimientos' => $this->getMovements(),
        ]);
    }
}
