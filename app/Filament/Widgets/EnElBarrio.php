<?php
namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\ActivitiesPeople;
use Illuminate\Support\Collection;

class EnElBarrio extends BaseWidget
{
    protected function getTableRecords()
    {
        $peopleInside = ActivitiesPeople::select('model_id', 'model')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->groupBy('model', 'model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get();

        $rows = collect();
        foreach ($peopleInside as $person) {
            $modelClass = "\\App\\Models\\" . $person->model;
            if (class_exists($modelClass)) {
                $instance = $modelClass::find($person->model_id);
                if ($instance) {
                    $lastEntry = ActivitiesPeople::join('activities', 'activities_people.activities_id', '=', 'activities.id')
                        ->where('activities_people.model', $person->model)
                        ->where('activities_people.model_id', $person->model_id)
                        ->where('activities.type', 'Entry')
                        ->orderByDesc('activities.created_at')
                        ->value('activities.created_at');

                    $rows->push((object)[
                        'first_name' => $instance->first_name ?? '',
                        'last_name' => $instance->last_name ?? '',
                        'type' => $person->model,
                        'last_entry' => $lastEntry,
                    ]);
                }
            }
        }
        return $rows;
    }

    public function table(Table $table): Table
    {
        return $table
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
