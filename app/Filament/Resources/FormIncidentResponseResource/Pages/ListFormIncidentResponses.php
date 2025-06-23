<?php

namespace App\Filament\Resources\FormIncidentResponseResource\Pages;

use App\Filament\Resources\FormIncidentResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormIncidentResponses extends ListRecords
{
    protected static string $resource = FormIncidentResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
