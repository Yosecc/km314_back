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
        $user = Auth::user();
        $today = now()->toDateString();
        
        // Contar formularios respondidos hoy por el usuario
        $todayCount = FormIncidentResponse::where('user_id', $user->id)
            ->where('date', $today)
            ->count();

        // Contar formularios de esta semana
        $weekCount = FormIncidentResponse::where('user_id', $user->id)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // Contar total de formularios del usuario
        $totalCount = FormIncidentResponse::where('user_id', $user->id)->count();

        // Verificar si ya fue marcado como visto hoy
        $cacheKey = "form_stats_viewed_{$user->id}_{$today}";
        $isViewed = Cache::get($cacheKey, false);

        return [
            Stat::make('Formularios Hoy', $todayCount)
                ->description($todayCount > 0 ? '¡Buen trabajo!' : 'Ningún formulario completado hoy')
                ->descriptionIcon($todayCount > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($todayCount > 0 ? 'success' : 'gray')
                ->chart([7, 4, 6, 8, 3, 5, $todayCount]) // Datos simulados para el gráfico
                ->extraAttributes([
                    'class' => $isViewed ? 'opacity-60' : '',
                ]),

            Stat::make('Esta Semana', $weekCount)
                ->description('Formularios completados')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Total', $totalCount)
                ->description('Todos tus formularios')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }

    public static function canView(): bool
    {
        // Solo mostrar si el usuario tiene formularios obligatorios asignados
        $user = Auth::user();
        return $user && $user->formIncidentRequirements()->active()->exists();
    }

    public function markAsViewed(): void
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $cacheKey = "form_stats_viewed_{$user->id}_{$today}";
        
        // Marcar como visto por 24 horas
        Cache::put($cacheKey, true, now()->addDay());
        
        // Refrescar el widget
        $this->dispatch('$refresh');
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $cacheKey = "form_stats_viewed_{$user->id}_{$today}";
        $isViewed = Cache::get($cacheKey, false);

        // Solo mostrar el botón si es admin y no ha sido visto
        if (!$isViewed && $user->hasRole('super_admin')) {
            return [
                \Filament\Actions\Action::make('markViewed')
                    ->label('Marcar como visto')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->size('sm')
                    ->action('markAsViewed')
            ];
        }

        return [];
    }
}
