<?php

namespace App\Filament\Resources\LoginLogResource\Pages;

use App\Filament\Resources\LoginLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoginLog extends EditRecord
{
    protected static string $resource = LoginLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
