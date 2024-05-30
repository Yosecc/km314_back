<?php

namespace App\Filament\Resources\InterestedResource\Pages;

use App\Filament\Resources\InterestedResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInteresteds extends ManageRecords
{
    protected static string $resource = InterestedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    
}
