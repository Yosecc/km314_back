<?php

namespace App\Filament\Resources\ConstructionStatusResource\Pages;

use App\Filament\Resources\ConstructionStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageConstructionStatuses extends ManageRecords
{
    protected static string $resource = ConstructionStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
