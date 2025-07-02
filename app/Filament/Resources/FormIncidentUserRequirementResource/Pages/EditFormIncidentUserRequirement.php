<?php

namespace App\Filament\Resources\FormIncidentUserRequirementResource\Pages;

use App\Filament\Resources\FormIncidentUserRequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormIncidentUserRequirement extends EditRecord
{
    protected static string $resource = FormIncidentUserRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
