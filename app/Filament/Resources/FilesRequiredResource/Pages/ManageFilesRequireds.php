<?php

namespace App\Filament\Resources\FilesRequiredResource\Pages;

use App\Filament\Resources\FilesRequiredResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFilesRequireds extends ManageRecords
{
    protected static string $resource = FilesRequiredResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
