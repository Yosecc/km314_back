<?php

namespace App\Filament\Resources\IncidentResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Incident;

class IncidentesStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Incidentes de Hoy', $this->incidentesHoy()),
        ];
    }

    public function incidentesHoy(): int
    {
        return Incident::whereDate('date_incident',now())->count() ;
    }
}
