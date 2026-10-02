<?php
namespace App\Filament\Resources\PackageReceptionResource\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Filament\Pages\PackageReceptionMonitor;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackageReceptions extends ListRecords
{
    protected static string $resource = PackageReceptionResource::class;

    public function getSubheading(): ?string
    {
        return 'La recepción de paquetes es un servicio adicional no incluido en la cuota de mantenimiento. Actualmente se brinda sin costo. El personal de Acceso no se responsabiliza por daños, pérdidas ni por el estado del contenido o del embalaje.';
    }

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
