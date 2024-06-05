<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Owner;
use Filament\Tables\Table;
use App\Models\ActivitiesPeople;
use App\Models\FormControlPeople;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class VisitantesEnElBarrio extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -5;
    protected static ?string $heading = 'Visitantes en el barrio (Entrada general - Club playa - Club House)';
    

    public function table(Table $table): Table
    {

        $validAccessTypes = ['general', 'playa', 'house'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($validAccessTypes) {
                foreach ($validAccessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            // ->count()
            ;

        return $table->heading(self::$heading)
            ->query(
                FormControlPeople::query()->whereIn('id',$peopleInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__("general.FirstName"))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__("general.LastName"))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
                Tables\Columns\TextColumn::make('formControl.access_type')
                    ->badge()
                    ->label(__("general.TypeActivitie"))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'Entrada general',
                        'playa' => 'Clud playa',
                        'hause' => 'Club hause',
                        'lote' => 'Lote',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'playa' => 'gray',
                        'hause' => 'gray',
                        'lote' => 'gray',
                    })->searchable(),
               
            ]);
    }
}
