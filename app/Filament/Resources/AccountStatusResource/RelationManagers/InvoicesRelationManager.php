<?php

namespace App\Filament\Resources\AccountStatusResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\Concerns\InteractsWithPageTable;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';
    protected static ?string $title = 'Facturas';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('period')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('public_identifier')->label('Folio'),
                TextColumn::make('items.description')->label('Conceptos')->limit(40),
                TextColumn::make('total')->label('Monto')->money('mxn')->color('primary'),
            ])
            ->filters([
                // Puedes agregar filtros personalizados aquí
            ]);
    }
}
