<?php

namespace App\Filament\Resources\FormControlResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class FormControlStats extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        return [
            Stat::make('Unique views', '192.1k'),
        ];
    }
}
