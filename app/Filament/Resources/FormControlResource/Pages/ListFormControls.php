<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use App\Filament\Resources\FormControlResource\Pages\Concerns\HasPublicFormLinkAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormControls extends ListRecords
{
    use HasPublicFormLinkAction;

    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sharePublicFormAction(),
            Actions\CreateAction::make(),
        ];
    }
}
