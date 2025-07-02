<?php

namespace App\Filament\Resources\FormIncidentUserRequirementResource\Pages;

use App\Filament\Resources\FormIncidentUserRequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormIncidentUserRequirements extends ListRecords
{
    protected static string $resource = FormIncidentUserRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
