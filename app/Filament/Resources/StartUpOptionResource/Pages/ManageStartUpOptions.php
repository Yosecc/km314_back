<?php

namespace App\Filament\Resources\StartUpOptionResource\Pages;

use App\Filament\Resources\StartUpOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStartUpOptions extends ManageRecords
{
    protected static string $resource = StartUpOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
