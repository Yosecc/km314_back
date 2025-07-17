<?php
<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\ActivitiesPeople;
use Illuminate\Support\Collection;

class EnElBarrio extends BaseWidget
{
    public function table(Table $table): Table
    {
        // Buscar todos los que están dentro, sin filtrar por tipo
        $peopleInside = ActivitiesPeople::select('model_id', 'model')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->groupBy('model', 'model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get();

        // Mapear los datos a una colección con nombre, apellido y tipo
        $rows = collect();
        foreach ($peopleInside as $person) {
            $modelClass = "\\App\\Models\\" . $person->model;
            if (class_exists($modelClass)) {
                $instance = $modelClass::find($person->model_id);
                if ($instance) {
                    $rows->push([
                        'first_name' => $instance->first_name ?? '',
                        'last_name' => $instance->last_name ?? '',
                        'type' => $person->model,
                        'last_entry' => optional($instance->activitiesPeople()->latest('created_at')->first())->created_at,
                    ]);
                }
            }
        }

        return $table
            ->query(
                // Usamos una colección en memoria
                $rows
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Nombre'),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido'),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->formatStateUsing(fn($state) => match($state) {
                    'Owner' => 'Propietario',
                    'OwnerFamily' => 'Familiar',
                    'Employee' => 'Empleado',
                    'OwnerSpontaneousVisit' => 'Visita espontánea',
                    'FormControlPeople' => 'Visitante',
                    'Tenant' => 'Inquilino',
                    default => $state,
                }),
                Tables\Columns\TextColumn::make('last_entry')->label('Última entrada')->dateTime(),
            ]);
    }
}
