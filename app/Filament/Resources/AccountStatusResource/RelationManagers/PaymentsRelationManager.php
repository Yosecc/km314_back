<?php

namespace App\Filament\Resources\AccountStatusResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\Concerns\InteractsWithPageTable;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Pagos';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_date')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('notes')->label('Notas')->limit(40),
                TextColumn::make('amount')->label('Monto')->money('mxn')->color('success'),
            ])
            ->filters([
                // Puedes agregar filtros personalizados aquí
            ]);
    }
}
