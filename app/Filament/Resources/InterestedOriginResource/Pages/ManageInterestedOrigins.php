<?php

namespace App\Filament\Resources\InterestedOriginResource\Pages;

use App\Filament\Resources\InterestedOriginResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInterestedOrigins extends ManageRecords
{
    protected static string $resource = InterestedOriginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
