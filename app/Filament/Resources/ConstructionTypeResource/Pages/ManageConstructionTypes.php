<?php

namespace App\Filament\Resources\ConstructionTypeResource\Pages;

use App\Filament\Resources\ConstructionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageConstructionTypes extends ManageRecords
{
    protected static string $resource = ConstructionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
