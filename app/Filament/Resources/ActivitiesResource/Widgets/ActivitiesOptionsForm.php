<?php

namespace App\Filament\Resources\ActivitiesResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivitiesOptionsForm extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Recorder entry', 'Entry')
                // ->description('32k increase')
                ->icon('heroicon-m-arrow-down-right')
                // ->url('/activities/create?type=Entry')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'processed' })",
                ])
                // ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->color('success')
                ,
        ];
    }

    public function setStatusFilter($data){
        dd($data);
    }
}
