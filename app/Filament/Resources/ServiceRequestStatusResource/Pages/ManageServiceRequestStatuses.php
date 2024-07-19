<?php

namespace App\Filament\Resources\ServiceRequestStatusResource\Pages;

use App\Filament\Resources\ServiceRequestStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceRequestStatuses extends ManageRecords
{
    protected static string $resource = ServiceRequestStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
