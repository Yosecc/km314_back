<?php
namespace App\Filament\Widgets;

use App\Models\ActivitiesPeople;
use App\Models\OwnerFamily;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class EnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -8;
    protected static ?string $heading = 'PERSONAS EN EL BARRIO';

    public function table(Table $table): Table
    {
        // IDs de familiares dentro
        $familysInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'OwnerFamily')
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        // IDs de empleados dentro
        $employeesInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Employee')
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        // Subqueries para cada tipo
        $families = OwnerFamily::query()
            ->whereIn('id', $familysInside)
            ->selectRaw('id, first_name, last_name, "Familiar" as tipo');
        $employees = Employee::query()
            ->whereIn('id', $employeesInside)
            ->selectRaw('id, first_name, last_name, "Empleado" as tipo');

        // Union y subconsulta para paginación/ordenamiento
        $union = $families->unionAll($employees);
        $sub = DB::query()->fromSub($union, 'personas_en_el_barrio');

        return $table
            ->heading(self::$heading)
            ->query($sub)
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__('general.FirstName'))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__('general.LastName'))->searchable(),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->searchable(),
            ]);
    }
}

