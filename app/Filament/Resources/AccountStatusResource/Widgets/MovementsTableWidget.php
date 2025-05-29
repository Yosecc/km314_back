<?php

namespace App\Filament\Resources\AccountStatusResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class MovementsTableWidget extends BaseWidget
{
    public ?object $record = null;
    use HasWidgetShield;
    // protected static string $view = 'filament.resources.account-status-resource.widgets.movements-table-widget';

    public function table(Table $table): Table
    {
        $movimientos = collect($this->getMovements());
        return $table
            ->paginated([5, 10, 15, 'all'])
            ->defaultPaginationPageOption(10)
            ->records($movimientos)
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y')),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('descripcion')->label('Descripción'),
                Tables\Columns\TextColumn::make('monto')->label('Monto')->formatStateUsing(fn($state, $record) => number_format($state, 2, ',', '.'))
                    ->color(fn($record) => $record['tipo'] === 'Pago' ? 'success' : ($record['monto'] < 0 ? 'danger' : 'primary')),
            ]);
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

    // public function render(): View
    // {
    //     return view(static::$view, [
    //         'movimientos' => $this->getMovements(),
    //     ]);
    // }
}
