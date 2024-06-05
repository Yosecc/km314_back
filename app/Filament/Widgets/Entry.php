<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class Entry extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = -99;
    // protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Atajos de actividades (entrada y salida)';
    protected function getColumns(): int
    {
        return 2;
    }    
    
    protected function getStats(): array
    {
        
        // if(!Auth::user()->hasAnyRole([2])){
        //     return [];
        // }

        return [
            Stat::make(__('general.RecorderEntry'), __('general.Entry'))
                // ->description('32k increase')
                ->icon('heroicon-m-arrow-down-right')
                ->url('/activities/create?type=Entry')
                // ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->color('success')
                ,
                Stat::make(__('general.RecorderExit'), __('general.Exit'))
                // ->description('32k increase')
                ->icon('heroicon-m-arrow-up-right')
                ->url('/activities/create?type=Exit')
                // ->descriptionIcon('heroicon-m-arrow-trending-up')
                // ->color('success')
                ,
           
        ];
    }
}
