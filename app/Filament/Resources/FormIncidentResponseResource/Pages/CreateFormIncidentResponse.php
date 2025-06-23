<?php

namespace App\Filament\Resources\FormIncidentResponseResource\Pages;

use App\Filament\Resources\FormIncidentResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFormIncidentResponse extends CreateRecord
{
    protected static string $resource = FormIncidentResponseResource::class;

    protected function getCreatedNotificationRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
