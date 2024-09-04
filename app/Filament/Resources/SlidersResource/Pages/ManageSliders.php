<?php

namespace App\Filament\Resources\SlidersResource\Pages;

use App\Filament\Resources\SlidersResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSliders extends ManageRecords
{
    protected static string $resource = SlidersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
