<?php

namespace App\Filament\Resources\TerminosCondicionesResource\Pages;

use App\Filament\Resources\TerminosCondicionesResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTerminosCondiciones extends ManageRecords
{
    protected static string $resource = TerminosCondicionesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
