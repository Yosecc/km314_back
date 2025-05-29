<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class MovementsPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static string $view = 'filament.pages.movements-page';
    protected static ?string $title = 'Movimientos';
    protected static ?string $navigationGroup = 'Administración contable';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getMovementsQuery())
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->sortable()->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y')),
                TextColumn::make('tipo')->label('Tipo')->badge(),
                TextColumn::make('descripcion')->label('Descripción'),
                TextColumn::make('monto')->label('Monto')->money('mxn')->color(fn($record) => $record->tipo === 'Pago' ? 'success' : ($record->monto < 0 ? 'danger' : 'primary')),
            ])
            ->filters([
                // Puedes agregar filtros personalizados aquí
            ]);
    }

    protected function getMovementsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $invoices = \App\Models\Invoice::query()
            ->selectRaw("period as fecha, 'Factura' as tipo, CONCAT(public_identifier, ' ', IFNULL((SELECT GROUP_CONCAT(description SEPARATOR ' + ') FROM invoice_items WHERE invoice_id = invoices.id), '')) as descripcion, total as monto")
            ->whereNotNull('owner_id');

        $payments = \App\Models\Payment::query()
            ->selectRaw("payment_date as fecha, 'Pago' as tipo, notes as descripcion, amount as monto")
            ->whereNotNull('owner_id');

        $union = $invoices->unionAll($payments);

        // Creamos un modelo Eloquent dinámico para envolver la subconsulta
        return (new \App\Models\Invoice())
            ->newQuery()
            ->fromSub($union, 'movimientos')
            ->select('*');
    }
}
