<?php

namespace App\Filament\Resources\FormIncidentResponseResource\Pages;

use App\Filament\Resources\FormIncidentResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormIncidentResponse extends EditRecord
{
    protected static string $resource = FormIncidentResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
