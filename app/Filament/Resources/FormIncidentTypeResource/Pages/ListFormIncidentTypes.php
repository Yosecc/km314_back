<?php

namespace App\Filament\Resources\FormIncidentTypeResource\Pages;

use App\Filament\Resources\FormIncidentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormIncidentTypes extends ListRecords
{
    protected static string $resource = FormIncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
