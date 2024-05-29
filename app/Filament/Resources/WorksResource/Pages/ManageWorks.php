<?php

namespace App\Filament\Resources\WorksResource\Pages;

use App\Filament\Resources\WorksResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWorks extends ManageRecords
{
    protected static string $resource = WorksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
