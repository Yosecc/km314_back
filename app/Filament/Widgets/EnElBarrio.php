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

    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection
    {
        $rows = collect();

        // Owner
        $ownerIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Owner')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();
        foreach (\App\Models\Owner::whereIn('id', $ownerIds)->get() as $owner) {
            $rows->push((object)[
                'first_name' => $owner->first_name,
                'last_name' => $owner->last_name,
                'tipo' => 'Propietario',
            ]);
        }

        // OwnerFamily
        $familyIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'OwnerFamily')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();
        foreach (\App\Models\OwnerFamily::whereIn('id', $familyIds)->get() as $family) {
            $rows->push((object)[
                'first_name' => $family->first_name,
                'last_name' => $family->last_name,
                'tipo' => 'Familiar',
            ]);
        }

        // Employee
        $employeeIds = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Employee')
            ->groupBy('model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();
        foreach (\App\Models\Employee::whereIn('id', $employeeIds)->get() as $employee) {
            $rows->push((object)[
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'tipo' => 'Empleado',
            ]);
        }

        // ...agrega más tipos si lo necesitas...

        return \Illuminate\Database\Eloquent\Collection::make($rows);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Nombre'),
                Tables\Columns\TextColumn::make('last_name')->label('Apellido'),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo'),
            ]);
    }
}
