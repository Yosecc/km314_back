<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormControls extends ListRecords
{
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
