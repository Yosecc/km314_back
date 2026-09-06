<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Pages\PackageReceptionMonitor;
use App\Models\PackageReception;
use Filament\Widgets\Widget;

class PackageReceptionStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.package-reception-stats';

    protected static ?int $sort = -8;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Recepción de Paquetes';
    }

    public function getViewData(): array
    {
        $base = PackageReception::query()->visibleTo(auth()->user());
        $expected = (clone $base)->where('status', PackageReception::EXPECTED)->count();
        $received = (clone $base)->where('status', PackageReception::RECEIVED)->count();
        $overdue = (clone $base)->where('status', PackageReception::EXPECTED)->where('expected_until', '<', now())->count();
        $pickup = (clone $base)->where('status', PackageReception::RECEIVED)
            ->where('received_at', '<=', now()->subHours(config('package-receptions.pickup_warning_hours')))->count();
        $canOpenMonitor = PackageReceptionMonitor::canAccess();

        return ['canOpenMonitor' => $canOpenMonitor, 'monitorUrl' => PackageReceptionMonitor::getUrl(), 'cards' => [
            ['label' => 'Esperados', 'value' => $expected, 'status' => 'expected', 'tone' => 'expected', 'icon' => 'heroicon-o-calendar-days'],
            ['label' => 'En recepción', 'value' => $received, 'status' => 'received', 'tone' => 'received', 'icon' => 'heroicon-o-inbox-arrow-down'],
            ['label' => 'No llegaron', 'value' => $overdue, 'status' => 'alerts', 'tone' => 'overdue', 'icon' => 'heroicon-o-clock', 'detail' => 'Fuera del horario previsto'],
            ['label' => 'Retiro demorado', 'value' => $pickup, 'status' => 'alerts', 'tone' => 'pickup', 'icon' => 'heroicon-o-exclamation-triangle', 'detail' => 'Pendientes de entrega'],
        ]];
    }
}
