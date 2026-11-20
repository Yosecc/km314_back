<?php

namespace App\Filament\Resources\InterestedStatusResource\Pages;

use App\Filament\Resources\InterestedStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInterestedStatuses extends ManageRecords
{
    protected static string $resource = InterestedStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
