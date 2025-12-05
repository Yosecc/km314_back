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
use Filament\Notifications\Actions\Action as ActionNotification;
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

    protected function isSalidaValidate($peopleIds, $model): void
    {
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
    }

    protected function isSalidaValidateSpontaneous($visitantesIds): void
    {
        // Validar salida de visitantes espontáneos
        $visitantesInside = ActivitiesPeople::whereIn('model_id', $visitantesIds)
            ->where('model', 'OwnerSpontaneousVisit')
            ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
            ->select('activities_people.model_id', DB::raw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) as entries'), DB::raw('SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END) as exits'))
            ->groupBy('activities_people.model_id')
            ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
            ->pluck('model_id')->toArray();

        $visitantesNotInside = $visitantesIds->diff($visitantesInside);

        if ($visitantesNotInside->isNotEmpty()) {
            $visitantes = OwnerSpontaneousVisit::whereIn('id', $visitantesNotInside->toArray())->get();
            $nombres = $visitantes->map(function($visitante) {
                return $visitante->first_name . ' ' . $visitante->last_name;
            });

            Notification::make()
                ->title('Algunos visitantes espontáneos no han entrado aún: ' . implode(', ', $nombres->toArray()))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function isEntradaValidate($peopleIds, $model): void
    {
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

    protected function isEntradaValidateSpontaneous($visitantesIds): void
    {
        // Validar entrada de visitantes espontáneos
        $visitantesAlreadyInside = ActivitiesPeople::whereIn('model_id', $visitantesIds)
                ->where('model', 'OwnerSpontaneousVisit')
                ->join('activities', 'activities_people.activities_id', '=', 'activities.id')
                ->select('activities_people.model_id', DB::raw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) as entries'), DB::raw('SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END) as exits'))
                ->groupBy('activities_people.model_id')
                ->havingRaw('SUM(CASE WHEN activities.type = "Entry" THEN 1 ELSE 0 END) > SUM(CASE WHEN activities.type = "Exit" THEN 1 ELSE 0 END)')
                ->pluck('model_id')->toArray();

        $visitantesAlreadyInside = $visitantesIds->intersect($visitantesAlreadyInside);

        if ($visitantesAlreadyInside->isNotEmpty()) {
            $visitantes = OwnerSpontaneousVisit::whereIn('id', $visitantesAlreadyInside->toArray())->get();
            $nombres = $visitantes->map(function($visitante) {
                return $visitante->first_name . ' ' . $visitante->last_name;
            });

            Notification::make()
                ->title('Algunos visitantes espontáneos no han salido aún: ' . implode(', ', $nombres->toArray()))
                ->danger()
                ->send();
            $this->halt();
        }
    }

    protected function beforeCreate(): void
    {
        // Runs before the form fields are saved to the database.

        $model = '';

        //dd($this->data);

        /** @var \Illuminate\Support\Collection<int, int> $peopleIds */
        $peopleIds = collect($this->data['peoples']);

        if($this->data['type'] ==  1) {
            $this->data['type'] = 'Entry';
        }elseif($this->data['type'] ==  2){
            $this->data['type'] = 'Exit';
        }


        if($this->data['tipo_entrada'] == 1){
            $model = 'Owner';
        }else if ($this->data['tipo_entrada'] == 2){
            $model = 'Employee';

            $employees = Employee::whereIn('id', $peopleIds)->where('owner_id','!=', null)->get();

            if($employees && $employees->count()){

                $employees->each(function($employee) use (&$peopleIds){
                    // dd($employee);

                    if($employee->owner){

                        if(!$employee->isFormularios()){
                            Notification::make()
                                ->title('El empleado '.$employee->first_name.' '.$employee->last_name.' requiere un formulario. El propietario debe actualizar o crear un formulario para este empleado.')
                                ->danger()
                                ->send();

                            $this->halt();
                        }else{

                            $employee->getFormularios()->each(function($formControl) use($employee, &$peopleIds){
                                $ids = $formControl->peoples->where('dni', $employee->dni)->pluck('id');

                                $peopleIds = $peopleIds->filter(function($id) use ($employee) {
                                    return (int)$id !== (int)$employee->id;
                                })->merge($ids);

                            });

                        }

                    }

                    if ($this->data['type'] == 'Entry') {

                        $isValidHorario = $employee->validaHorarios();

                        if (!$isValidHorario['status']) {
                            session()->put('force_create', $this->data['is_force']);

                            Notification::make()
                                ->title($isValidHorario['mensaje'])
                                ->danger()
                                // ->actions([
                                //     ActionNotification::make('force')
                                //         ->label('Forzar envío')
                                //         ->button()
                                //         ->color('danger')
                                //         ->action(function () {
                                //             session()->put('force_create', true);
                                //         }),
                                // ])
                                ->send();

                            if (!session()->get('force_create')) {
                                $this->halt();
                            }
                        }

                    }

                });

                $model = 'FormControl';

            }

        }else if($this->data['tipo_entrada'] == 3){
            $model = 'FormControl';
        }



        if ($this->data['type'] == 'Exit') {
            $this->isSalidaValidate($peopleIds, $model);
            
            // Validar visitantes espontáneos
            if (!empty($this->data['spontaneous_visit'])) {
                $visitantesIds = collect($this->data['spontaneous_visit']);
                $this->isSalidaValidateSpontaneous($visitantesIds);
            }
        } else if ($this->data['type'] == 'Entry') {
            $this->isEntradaValidate($peopleIds, $model);
            
            // Validar visitantes espontáneos
            if (!empty($this->data['spontaneous_visit'])) {
                $visitantesIds = collect($this->data['spontaneous_visit']);
                $this->isEntradaValidateSpontaneous($visitantesIds);
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

        $formControlId = null;

        $record = $this->record;
        $people = collect($this->data['peoples'])
                    ->map(function($people) use (&$model, $record, &$formControlId ){
                        $type = null;
                        if($model == 'Employee'){
                            $empleado = Employee::where('id', $people)->first();
                            if($empleado->owner_id && $empleado->isFormularios()){
                                $peopleFormControlPeople = $empleado->formControlPeople($empleado->dni);
                                if($peopleFormControlPeople){
                                    $model = 'FormControl';
                                    $type = 'Employee';
                                    $formControlId = $peopleFormControlPeople->form_control_id;
                                    $people = $peopleFormControlPeople->id;
                                }
                            }
                        }
                        return [
                            'activities_id' => $record->id ,
                            'model' => $model,
                            'model_id' => $people,
                            'type' => $type,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    });

        if($formControlId){
            $record->form_control_id = $formControlId;
            $record->save();
        }

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

                    if ($this->data['type'] == 'Exit') {
                        $vis->salida = 1;
                    }else{
                        $vis->agregado = 1;
                    }

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
