<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Traits\HasQrCodeAction;

class ViewFormControl extends ViewRecord
{
    use HasQrCodeAction;
    
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getQrCodeAction(),
            Actions\EditAction::make(),
        ];
    }
}
