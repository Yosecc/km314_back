<?php

namespace App\Filament\Widgets;

use App\Models\FormIncidentResponse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FormIncidentStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Contar formularios sin leer de hoy
        $unreadTodayCount = FormIncidentResponse::unread()
            ->where('date', now()->toDateString())
            ->count();

        // Contar formularios sin leer de esta semana
        $unreadWeekCount = FormIncidentResponse::unread()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // Contar total de formularios sin leer
        $totalUnreadCount = FormIncidentResponse::unread()->count();

        return [
            Stat::make('Sin leer hoy', $unreadTodayCount)
                ->description($unreadTodayCount > 0 ? 'Formularios pendientes de revisar' : 'Todos los formularios de hoy revisados')
                ->descriptionIcon($unreadTodayCount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($unreadTodayCount > 0 ? 'warning' : 'success')
                ->chart([12, 8, 15, 10, 6, 9, $unreadTodayCount]),

            Stat::make('Sin leer esta semana', $unreadWeekCount)
                ->description('Formularios pendientes')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($unreadWeekCount > 0 ? 'warning' : 'success'),

            Stat::make('Total sin leer', $totalUnreadCount)
                ->description('Todos los formularios pendientes')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($totalUnreadCount > 0 ? 'danger' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        // Solo mostrar a usuarios que sean super_admin
        $user = Auth::user();
        return $user && $user->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
