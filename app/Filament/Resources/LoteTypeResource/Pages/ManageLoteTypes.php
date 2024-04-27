<?php

namespace App\Filament\Resources\LoteTypeResource\Pages;

use App\Filament\Resources\LoteTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLoteTypes extends ManageRecords
{
    protected static string $resource = LoteTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
