<?php

namespace App\Filament\Resources\ConfigWebResource\Pages;

use App\Filament\Resources\ConfigWebResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConfigWebs extends ListRecords
{
    protected static string $resource = ConfigWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
