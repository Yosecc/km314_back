<?php

namespace App\Filament\Resources\ConfigWebResource\Pages;

use App\Filament\Resources\ConfigWebResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfigWeb extends EditRecord
{
    protected static string $resource = ConfigWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


}
