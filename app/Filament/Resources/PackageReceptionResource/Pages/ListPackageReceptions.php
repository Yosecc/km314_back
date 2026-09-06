<?php
namespace App\Filament\Resources\PackageReceptionResource\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Filament\Pages\PackageReceptionMonitor;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackageReceptions extends ListRecords
{
    protected static string $resource = PackageReceptionResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('monitor')
                ->label('Abrir monitor')
                ->icon('heroicon-o-signal')
                ->url(PackageReceptionMonitor::getUrl())
                ->visible(fn (): bool => PackageReceptionMonitor::canAccess()),
            Actions\CreateAction::make(),
        ];
    }
}
