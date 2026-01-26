<?php

namespace App\Filament\Resources\ActivitiesResource\Widgets;

use Filament\Tables;
use App\Models\Activities;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class UltimasActividades extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    use HasWidgetShield;
    protected static ?string $heading = 'Últimas actividades';

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
                        'hause' => 'Club house',
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
}
