<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Employee;
use Filament\Tables\Table;
use App\Models\ActivitiesPeople;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class EmpleadosEnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -6;  
    protected static ?string $heading = 'Empleados en el barrio';
   
    public function table(Table $table): Table
    {

        $peopleInside = ActivitiesPeople::select('model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->groupBy('model_id')
            ->where('model','Employee')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->distinct('model_id')
            ->get()
            ;

            
        return $table
        ->heading('Últimas actividades')
            ->heading(self::$heading)
            ->query(
                Employee::query()->whereIn('id',$peopleInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__("general.FirstName"))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__("general.LastName"))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
            ]);
    }
}
