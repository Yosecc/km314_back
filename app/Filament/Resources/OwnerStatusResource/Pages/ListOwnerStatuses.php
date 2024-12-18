<?php

namespace App\Filament\Resources\OwnerStatusResource\Pages;

use App\Filament\Resources\OwnerStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerStatuses extends ListRecords
{
    protected static string $resource = OwnerStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
