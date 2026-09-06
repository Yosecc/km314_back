<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Models\ActivitiesPeople;
use App\Models\Owner;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PropietariosEnElBarrio extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = -7;

    protected static ?string $heading = 'Propietarios en el barrio';

    public function table(Table $table): Table
    {
        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'Owner')
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get();

        return $table
            ->heading(self::$heading)
            ->query(
                Owner::query()->whereIn('id', $peopleInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__('general.FirstName'))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__('general.LastName'))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
            ]);
    }
}
