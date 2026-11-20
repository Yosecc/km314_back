<?php

namespace App\Filament\Resources\InterestedResource\Pages;

use App\Filament\Pages\InterestedCalendar;
use App\Filament\Pages\InterestedMonitor;
use App\Filament\Resources\InterestedResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInteresteds extends ManageRecords
{
    protected static string $resource = InterestedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('monitor')
                ->label('Abrir monitor')
                ->icon('heroicon-o-signal')
                ->url(InterestedMonitor::getUrl())
                ->visible(fn () => InterestedMonitor::canAccess()),
            Actions\Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->url(InterestedCalendar::getUrl())
                ->visible(fn () => InterestedCalendar::canAccess()),
            Actions\CreateAction::make(),
        ];
    }
}
