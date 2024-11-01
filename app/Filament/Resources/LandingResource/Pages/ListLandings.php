<?php

namespace App\Filament\Resources\LandingResource\Pages;

use App\Filament\Resources\LandingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandings extends ListRecords
{
    protected static string $resource = LandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
