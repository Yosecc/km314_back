<?php
namespace App\Filament\Widgets;

use App\Models\ActivitiesPeople;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use App\Models\OwnerFamily;


class EnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -8;
    protected static ?string $heading = 'PERSONAS EN EL BARRIO';

    public function table(Table $table): Table
    {
        $FamilysInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->where('activities_people.model', 'OwnerFamily')
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get();

        return $table
            ->heading(self::$heading)
            ->query(
                OwnerFamily::query()->whereIn('id',$FamilysInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__("general.FirstName"))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__("general.LastName"))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
            ]);
    }
}

