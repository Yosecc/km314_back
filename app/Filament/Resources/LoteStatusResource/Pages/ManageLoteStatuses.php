<?php

namespace App\Filament\Resources\LoteStatusResource\Pages;

use App\Filament\Resources\LoteStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLoteStatuses extends ManageRecords
{
    protected static string $resource = LoteStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
