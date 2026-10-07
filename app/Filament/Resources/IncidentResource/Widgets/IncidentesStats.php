<?php

namespace App\Filament\Resources\IncidentResource\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Models\Incident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IncidentesStats extends BaseWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Estadísticas de Incidentes';

    public static function isVisible(): bool
    {
        return false;
    }

    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Incidentes de Hoy', $this->incidentesHoy()),
        ];
    }

    public function incidentesHoy(): int
    {
        return Incident::whereDate('date_incident', now())->count();
    }
}
