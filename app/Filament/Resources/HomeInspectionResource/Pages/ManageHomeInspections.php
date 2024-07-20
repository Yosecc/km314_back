<?php

namespace App\Filament\Resources\HomeInspectionResource\Pages;

use App\Filament\Resources\HomeInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHomeInspections extends ManageRecords
{
    protected static string $resource = HomeInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
