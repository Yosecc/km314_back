<?php

namespace App\Filament\Widgets;

use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Models\Owner;
use App\Models\OwnerSpontaneousVisit;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class VisitaEspontaneaEnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -9;
    protected static ?string $heading = 'Visitantes espontáneos';

    public function table(Table $table): Table
    {
        $FamilysInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'OwnerSpontaneousVisit')
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get();

        return $table
            ->heading(self::$heading)
            ->query(
                OwnerSpontaneousVisit::query()->whereIn('id',$FamilysInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__("general.FirstName"))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__("general.LastName"))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
            ]);
    }
}
