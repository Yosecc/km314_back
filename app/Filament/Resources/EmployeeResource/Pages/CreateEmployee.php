<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_vencimiento_seguro'])) {
            $data['fecha_vencimiento_seguro'] = Carbon::now()->addMonths(3)->toDateString();
        }
        return $data;
    }
}
