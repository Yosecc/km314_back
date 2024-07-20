<?php

namespace App\Filament\Resources\WorksAndInstallationResource\Pages;

use App\Filament\Resources\WorksAndInstallationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWorksAndInstallations extends ManageRecords
{
    protected static string $resource = WorksAndInstallationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
