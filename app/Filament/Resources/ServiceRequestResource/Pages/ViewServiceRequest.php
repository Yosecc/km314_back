<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequest extends ViewRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => ServiceRequestResource::canEdit($this->record)),
        ];
    }
}
