<?php

namespace App\Filament\Resources\TrabajosResource\Pages;

use App\Filament\Resources\TrabajosResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTrabajos extends ManageRecords
{
    protected static string $resource = TrabajosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
