<?php

namespace App\Filament\Resources\PopUpResource\Pages;

use App\Filament\Resources\PopUpResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePopUps extends ManageRecords
{
    protected static string $resource = PopUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
