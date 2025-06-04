<?php

namespace App\Filament\Resources\EmployeeResource\Pages;


use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function beforeFill(): void
    {
       dd('este es');
    }

}
