<?php
namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\ActivitiesPeople;
use Illuminate\Support\Collection;

class EnElBarrio extends BaseWidget
{
    public function query()
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
        return \Illuminate\Database\Eloquent\Collection::make($rows);
    }

    public function table(Table $table): Table
    {
        // IDs de cada tipo que están dentro
        $ownerIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Owner')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        $familyIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'OwnerFamily')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        $employeeIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Employee')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        // Builder para cada tipo con columna "tipo"
        $ownerQuery = \App\Models\Owner::query()
            ->whereIn('id', $ownerIds)
            ->selectRaw('id, first_name, last_name, "Propietario" as tipo');

        $familyQuery = \App\Models\OwnerFamily::query()
            ->whereIn('id', $familyIds)
            ->selectRaw('id, first_name, last_name, "Familiar" as tipo');

        $employeeQuery = \App\Models\Employee::query()
            ->whereIn('id', $employeeIds)
            ->selectRaw('id, first_name, last_name, "Empleado" as tipo');

        // Unir todos los builders
        $unionQuery = $ownerQuery
            ->unionAll($familyQuery)
            ->unionAll($employeeQuery);

        return $table
            ->query($unionQuery)
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Nombre'),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido'),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo'),
            ]);
    }
}
