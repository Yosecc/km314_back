<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ActivitiesPeople;
use App\Models\FormControlPeople;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class TrabajadoresEnElBarrio extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = -3;
    protected static ?string $heading = 'Trabajadores en el barrio (Lotes)';
    

    public function table(Table $table): Table
    {
        $accessTypes = ['lote'];
        $incomeTypes = ['Trabajador'];

        $peopleInside = ActivitiesPeople::select('activities_people.model_id')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->join('form_control_people', 'activities_people.model_id', '=', 'form_control_people.id')
            ->join('form_controls', 'form_control_people.form_control_id', '=', 'form_controls.id')
            ->where('activities_people.model', 'FormControl')
            ->where(function($query) use ($accessTypes) {
                foreach ($accessTypes as $type) {
                    $query->orWhere('form_controls.access_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->where(function($query) use ($incomeTypes) {
                foreach ($incomeTypes as $type) {
                    $query->orWhere('form_controls.income_type', 'LIKE', '%' . $type . '%');
                }
            })
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->get()
            ;

        return $table->heading(self::$heading)
            ->query(
                FormControlPeople::query()->whereIn('id',$peopleInside->pluck('model_id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label(__("general.FirstName"))->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label(__("general.LastName"))->searchable(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.created_at')->label(__('general.ultimaEntrada'))->searchable()->dateTime(),
                Tables\Columns\TextColumn::make('activitiePeople.activitie.lote_ids')->label(__('general.Lote'))->searchable(),
            ]);
    }
}
