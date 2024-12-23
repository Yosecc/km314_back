<?php

namespace App\Filament\Resources\ConstructionCompanieResource\Pages;

use App\Filament\Resources\ConstructionCompanieResource;
use App\Models\EmployeeSchedule;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConstructionCompanie extends EditRecord
{
    protected static string $resource = ConstructionCompanieResource::class;

    protected function afterSave(): void
    {
        if($this->data['is_horario']){
            $horarios = $this->record->horarios;
            $empleados = $this->record->empleados;
            // Generar combinaciones de empleados y horarios
            $schedulesToInsert = $empleados->flatMap(function ($empleado) use ($horarios) {
                return $horarios->map(function ($horario) use ($empleado) {

                    return [
                        [
                            'employee_id' => $empleado->id ,
                            'day_of_week' => $horario->day_of_week,
                        ],
                        [
                            'start_time' => $horario->start_time,
                            'end_time' => $horario->end_time,
                            'updated_at' => now(),
                        ]
                    ];
                });
            });

            $schedulesToInsert->values()->each(function($horario){
                EmployeeSchedule::updateOrInsert($horario[0],$horario[1]);
            });
        }
    }
}
