<?php

namespace App\Filament\Resources\FormIncidentTypeResource\Pages;

use App\Filament\Resources\FormIncidentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormIncidentType extends EditRecord
{
    protected static string $resource = FormIncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
