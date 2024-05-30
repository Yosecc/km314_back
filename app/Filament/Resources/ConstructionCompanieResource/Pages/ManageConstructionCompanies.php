<?php

namespace App\Filament\Resources\ConstructionCompanieResource\Pages;

use App\Filament\Resources\ConstructionCompanieResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageConstructionCompanies extends ManageRecords
{
    protected static string $resource = ConstructionCompanieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
