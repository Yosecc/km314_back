<?php

namespace App\Filament\Resources\CommonSpacesResource\Pages;

use App\Filament\Resources\CommonSpacesResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCommonSpaces extends ManageRecords
{
    protected static string $resource = CommonSpacesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
