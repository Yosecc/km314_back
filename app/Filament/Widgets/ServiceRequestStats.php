<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Pages\ServiceRequestMonitor;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Widgets\Widget;

class ServiceRequestStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.service-request-stats';

    protected static ?int $sort = -7;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Solicitudes de servicio';
    }

    public function getViewData(): array
    {
        $monthStart = now('America/Argentina/Buenos_Aires')->startOfMonth();
        $monthEnd = now('America/Argentina/Buenos_Aires')->endOfMonth();
        $base = ServiceRequest::query()
            ->visibleTo(auth()->user())
            ->whereBetween('created_at', [$monthStart, $monthEnd]);
        $pending = (clone $base)
            ->whereHas('serviceRequestStatus', fn ($query) => $query->where('code', ServiceRequestStatus::PENDING))
            ->count();
        $inProgress = (clone $base)
            ->whereHas('serviceRequestStatus', fn ($query) => $query->where('code', ServiceRequestStatus::IN_PROGRESS))
            ->count();
        $canOpenMonitor = ServiceRequestMonitor::canAccess();

        return [
            'canOpenMonitor' => $canOpenMonitor,
            'monitorUrl' => ServiceRequestMonitor::getUrl(),
            'cards' => [
                ['label' => 'Activas', 'value' => $pending + $inProgress, 'status' => 'active', 'tone' => 'active', 'icon' => 'heroicon-o-wrench-screwdriver'],
                ['label' => 'Pendientes', 'value' => $pending, 'status' => ServiceRequestStatus::PENDING, 'tone' => 'pending', 'icon' => 'heroicon-o-clock'],
                ['label' => 'En progreso', 'value' => $inProgress, 'status' => ServiceRequestStatus::IN_PROGRESS, 'tone' => 'progress', 'icon' => 'heroicon-o-arrow-path'],
            ],
        ];
    }
}
