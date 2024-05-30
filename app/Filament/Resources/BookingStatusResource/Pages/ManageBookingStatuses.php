<?php

namespace App\Filament\Resources\BookingStatusResource\Pages;

use App\Filament\Resources\BookingStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBookingStatuses extends ManageRecords
{
    protected static string $resource = BookingStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
