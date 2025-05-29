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
        return $table
            ->heading(self::$heading)
            ->paginated([5, 10, 15, 'all'])
            ->defaultPaginationPageOption(5)
            ->query(
                Activities::query()->orderBy('created_at','desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Entry' => __('general.Entry'),
                        'Exit' => __('general.Exit'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Entry' => 'success',
                        'Exit' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('tipo_entrada')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                         '1' => 'Propietarios',
                         '2' => 'Empleados',
                         '3' => 'Otros'
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray',
                        '2' => 'success',
                        '3' => 'warning'
                    }),
                Tables\Columns\TextColumn::make('lote_ids')
                    ->label(__('general.Lotes'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('formControl.access_type')
                    ->badge()
                    ->label(__("general.TypeActivitie"))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'Entrada general',
                        'playa' => 'Clud playa',
                        'hause' => 'Club hause',
                        'lote' => 'Lote',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'playa' => 'gray',
                        'hause' => 'gray',
                        'lote' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('formControl.income_type')
                    ->badge()
                    ->label(__("general.TypeIncome"))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Inquilino' => 'Inquilino',
                        'Trabajador' => 'Trabajador',
                        'Visita' => 'Visita'
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Inquilino' => 'success',
                        'Trabajador' => 'gray',
                        'Visita' => 'warning'
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable(),
            ]);
    }

    // public function getMovements(): array
    // {
    //     if (!$this->record) return [];
    //     $ownerId = $this->record->owner_id;
    //     $loteId = $this->record->lote_id ?? null;
    //     $invoices = \App\Models\Invoice::where('owner_id', $ownerId)
    //         ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
    //         ->get()
    //         ->map(function ($inv) {
    //             return [
    //                 'fecha' => $inv->period,
    //                 'tipo' => 'Factura',
    //                 'descripcion' => $inv->public_identifier . ' ' . ($inv->items->pluck('description')->join(' + ') ?? ''),
    //                 'monto' => $inv->total,
    //             ];
    //         });
    //     $payments = \App\Models\Payment::where('owner_id', $ownerId)
    //         ->when($loteId, fn($q) => $q->where('lote_id', $loteId))
    //         ->get()
    //         ->map(function ($pay) {
    //             return [
    //                 'fecha' => $pay->payment_date,
    //                 'tipo' => 'Pago',
    //                 'descripcion' => $pay->notes,
    //                 'monto' => $pay->amount,
    //             ];
    //         });
    //     $all = $invoices->concat($payments)->sortBy('fecha')->values();
    //     return $all->toArray();
    // }

    // public function render(): View
    // {
    //     return view(static::$view, [
    //         'movimientos' => $this->getMovements(),
    //     ]);
    // }
}
