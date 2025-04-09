<?php

namespace App\Filament\Resources\ServiceTypeResource\Pages;

use App\Filament\Resources\ServiceTypeResource;
use App\Models\ServiceType;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceTypes extends ManageRecords
{
    protected static string $resource = ServiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->after(function (ServiceType $record) {
                $record->update(['order' => ServiceType::count()]);
            }),
        ];
    }
}
