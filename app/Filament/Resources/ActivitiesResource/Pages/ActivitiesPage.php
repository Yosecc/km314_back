<?php

namespace App\Filament\Resources\ActivitiesResource\Pages;

use Carbon\Carbon;
use App\Models\Owner;
use Filament\Actions;
use App\Models\Employee;
use Filament\Actions\Action;
use App\Models\OwnerSpontaneousVisit;
use App\Models\ActivitiesAuto;
use App\Models\ActivitiesPeople;
use App\Models\FormControlPeople;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Resources\ActivitiesResource;

class ActivitiesPage extends CreateRecord
{
    protected static string $resource = ActivitiesResource::class;

    protected function getHeaderWidgets(): array
    {
        return [

            // \App\Filament\Resources\ActivitiesResource\Widgets\ActivitiesWidget::class,
            // \App\Filament\Resources\ActivitiesResource\Widgets\ActivitiesOptionsForm::class,
        ];
    }

    protected function beforeCreate(): void
    {
        // Runs before the form fields are saved to the database.

        $model = '';
        if($this->data['tipo_entrada'] == 1){
            $model = 'Owner';
        }else if ($this->data['tipo_entrada'] == 2){
            $model = 'Employee';
        }else if($this->data['tipo_entrada'] == 3){
            $model = 'FormControl';
        }

        $peopleIds = collect($this->data['peoples']);

        if ($this->data['type'] == 'Exit') {
            // Validar salida
            $peopleInside = ActivitiesPeople::whereIn('model_id', $peopleIds)
                ->where('model', $model)
                ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
                ->select('activities_people.model_id', DB::raw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) as entries'), DB::raw('SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END) as exits'))
                ->groupBy('activities_people.model_id')
                ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
                ->pluck('model_id')->toArray();

            $peopleNotInside = $peopleIds->diff($peopleInside);

            if ($peopleNotInside->isNotEmpty()) {
                // Retornar mensaje de error si alguna persona no ha entrado
                $personas = $this->getPeopleNames($peopleNotInside, $model);

                Notification::make()
                    ->title('Algunas personas no han entrado aún: ' . implode(', ', $personas->toArray()))
                    ->danger()
                    ->send();
                $this->halt();
            }
        } else if ($this->data['type'] == 'Entry') {
            // Validar entrada
            $peopleOutside = ActivitiesPeople::whereIn('model_id', $peopleIds)
                ->where('model', $model)
                ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
                ->select('activities_people.model_id', DB::raw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) as entries'), DB::raw('SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END) as exits'))
                ->groupBy('activities_people.model_id')
                ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
                ->pluck('model_id')->toArray();

            $peopleAlreadyInside = $peopleIds->intersect($peopleOutside);

            if ($peopleAlreadyInside->isNotEmpty()) {
                // Retornar mensaje de error si alguna persona ya está dentro
                $personas = $this->getPeopleNames($peopleAlreadyInside, $model);

                Notification::make()
                    ->title('Algunas personas no han salido aún: ' . implode(', ', $personas->toArray()))
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        if (!empty($this->data['spontaneous_visit'])) {
            $valores = collect($this->data['spontaneous_visit'])
                ->map(function ($visitante) {
                    return OwnerSpontaneousVisit::where('id', $visitante)
                        ->where('aprobado', 1)
                        ->exists();
                });

            // Si algún valor es `false`, se ejecuta `$this->halt()`
            if ($valores->contains(false)) {
                Notification::make()
                    ->title('Alguno de los visitantes espontaneos seleccionados no tiene permiso de entrada')
                    ->danger()
                    ->send();
                $this->halt();
            }
        }
    }

    private function getPeopleNames($peopleIds, $model)
    {
        if ($model == 'Owner') {
            $personas = Owner::whereIn('id', $peopleIds->toArray())->get();
        } else if ($model == 'Employee') {
            $personas = Employee::whereIn('id', $peopleIds->toArray())->get();
        } else if ($model == 'FormControl') {
            $personas = FormControlPeople::whereIn('id', $peopleIds->toArray())->get();
        }

        return $personas->map(function($people) {
            return $people['first_name'].' '.$people['last_name'];
        });
    }

    protected function afterCreate(): void
    {
        // dd('SI',$this->record, $this->data);

        $model = '';
        if($this->data['tipo_entrada'] == 1){
            $model = 'Owner';
        }else if ($this->data['tipo_entrada'] == 2){
            $model = 'Employee';
        }else if($this->data['tipo_entrada'] == 3){
            $model = 'FormControl';
        }

        $record = $this->record;
        $people = collect($this->data['peoples'])
                    ->map(function($people) use ($model, $record ){
                        return [
                            'activities_id' => $record->id ,
                            'model' => $model,
                            'model_id' => $people,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    });
        ActivitiesPeople::insert($people->toArray());

        if(isset($this->data['families']) && count($this->data['families'])){
            $familie = collect($this->data['families'])
                ->map(function($people) use ($model, $record ){
                    return [
                        'activities_id' => $record->id ,
                        'model' => 'OwnerFamily',
                        'model_id' => $people,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                });

            ActivitiesPeople::insert($familie->toArray());
        }

        if (!empty($this->data['spontaneous_visit'])) {
            $valores = collect($this->data['spontaneous_visit'])
                ->map(function ($visitante) use ($record) {
                    $vis = OwnerSpontaneousVisit::where('id', $visitante)->first();
                    $vis->agregado = 1;
                    $vis->save();

                    return [
                        'activities_id' => $record->id ,
                        'model' => 'OwnerSpontaneousVisit',
                        'model_id' => $visitante,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];
                });

                ActivitiesPeople::insert($valores->toArray());
        }



        $autos = collect($this->data['autos'])
                    ->map(function($auto) use ($model, $record ){
                        return [
                            'activities_id' => $record->id ,
                            'auto_id' => $auto,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    });

        ActivitiesAuto::insert($autos->toArray());
    }


}
