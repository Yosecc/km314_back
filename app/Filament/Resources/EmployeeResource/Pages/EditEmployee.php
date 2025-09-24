<?php

namespace App\Filament\Resources\EmployeeResource\Pages;


use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function beforeFill(): void
    {
     //  dd('este es');
    }

    protected function afterSave(): void
    {
        // Si es un owner y no está asociado, asociarlo
        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            if (!$this->record->owners()->where('owner_id', Auth::user()->owner_id)->exists()) {
                $this->record->owners()->attach(Auth::user()->owner_id);
            }
        }
    }


}
