<?php

namespace App\Filament\Resources\ConstructionCompanieResource\Pages;

use App\Filament\Resources\ConstructionCompanieResource;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateConstructionCompanie extends CreateRecord
{
    protected static string $resource = ConstructionCompanieResource::class;

    protected function afterCreate(): void
    {
         // Obtener los horarios y empleados
        // $horarios = $this->record->horarios;
        // $empleados = $this->record->empleados;
        // dd($horarios, $empleados, $this->record);
        // Generar combinaciones de empleados y horarios
        // $schedulesToInsert = $empleados->flatMap(function ($empleado) use ($horarios) {
        //     return $horarios->map(function ($horario) use ($empleado) {
        //         return [
        //             'employee_id' => $empleado->id,
        //             'day_of_week' => $horario->day_of_week,
        //             'start_time' => $horario->start_time,
        //             'end_time' => $horario->end_time,
        //             'created_at' => now(),
        //             'updated_at' => now(),
        //         ];
        //     });
        // });

        // // Inserción masiva
        // EmployeeSchedule::insert($schedulesToInsert->toArray());
    }
}
