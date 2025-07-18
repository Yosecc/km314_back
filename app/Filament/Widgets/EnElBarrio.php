<?php
namespace App\Filament\Widgets;

use App\Models\ActivitiesPeople;
use App\Models\Employee;
use App\Models\OwnerFamily;
use App\Models\PersonaEnElBarrio;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class EnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -8;
    protected static ?string $heading = 'PERSONAS EN EL BARRIO';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
        ->heading(self::$heading)
        ->query(PersonaEnElBarrio::query())
        ->defaultGroup('lote')
        ->columns([
            Tables\Columns\TextColumn::make('first_name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->label('Tipo')->searchable(),
            Tables\Columns\TextColumn::make('lote')
                ->label('Lote')
                ->searchable()
                ->summarize(Count::make()->label('Total personas')),
            Tables\Columns\TextColumn::make('ultima_entrada')->label('ultima_entrada')->searchable(),

        ]);
    }
}

