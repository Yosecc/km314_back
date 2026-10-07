<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Pages\MonitorAccesos;
use App\Services\AccessPeopleInsideService;
use Filament\Widgets\Widget;

class AccessControlStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.access-control-stats';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Control de Acceso';
    }

    public function getViewData(): array
    {
        $rows = AccessPeopleInsideService::rows();
        $counts = AccessPeopleInsideService::counts($rows);
        $cards = collect(AccessPeopleInsideService::categories())
            ->map(fn (array $category, string $key) => $category + [
                'key' => $key,
                'value' => $counts[$key] ?? 0,
                'url' => MonitorAccesos::getUrl(['inside' => $key]),
            ])
            ->values();

        return [
            'canOpenMonitor' => MonitorAccesos::canAccess(),
            'monitorUrl' => MonitorAccesos::getUrl(),
            'total' => $rows->count(),
            'cards' => $cards,
        ];
    }
}
