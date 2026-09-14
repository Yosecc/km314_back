<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Pages\ServiceRequestMonitor;
use App\Filament\Resources\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('monitor')
                ->label('Abrir monitor')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->url(ServiceRequestMonitor::getUrl())
                ->visible(fn () => ServiceRequestMonitor::canAccess()),
            Actions\CreateAction::make(),
        ];
    }
}
