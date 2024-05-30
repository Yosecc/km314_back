<?php

namespace App\Filament\Resources\InterestedTypeOperationResource\Pages;

use App\Filament\Resources\InterestedTypeOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInterestedTypeOperations extends ManageRecords
{
    protected static string $resource = InterestedTypeOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
