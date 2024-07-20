<?php

namespace App\Filament\Resources\StartUpResource\Pages;

use App\Filament\Resources\StartUpResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStartUps extends ManageRecords
{
    protected static string $resource = StartUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
