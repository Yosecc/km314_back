<?php

namespace App\Filament\Resources\OwnerStatusResource\Pages;

use App\Filament\Resources\OwnerStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerStatus extends EditRecord
{
    protected static string $resource = OwnerStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
