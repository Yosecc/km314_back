<?php

namespace App\Filament\Resources\InvoiceConfigResource\Pages;

use App\Filament\Resources\InvoiceConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceConfig extends EditRecord
{
    protected static string $resource = InvoiceConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
