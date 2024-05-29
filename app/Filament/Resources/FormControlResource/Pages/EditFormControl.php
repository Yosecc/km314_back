<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormControl extends EditRecord
{
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
