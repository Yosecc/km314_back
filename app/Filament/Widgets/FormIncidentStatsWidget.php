<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Resources\FormIncidentResponseResource;
use App\Models\FormIncidentResponse;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class FormIncidentStatsWidget extends Widget
{
    use HasWidgetShield {
        canView as protected shieldCanView;
    }

    protected static string $view = 'filament.widgets.form-incident-stats-widget';

    protected static ?int $sort = -98;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Formularios de incidentes sin leer';
    }

    public function getViewData(): array
    {
        $unreadTodayCount = FormIncidentResponse::unread()
            ->where('date', now()->toDateString())
            ->count();

        $unreadWeekCount = FormIncidentResponse::unread()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $totalUnreadCount = FormIncidentResponse::unread()->count();
        $canOpenList = FormIncidentResponseResource::canViewAny();

        return [
            'canOpenList' => $canOpenList,
            'listUrl' => FormIncidentResponseResource::getUrl('index'),
            'cards' => [
                [
                    'label' => 'Sin leer hoy',
                    'value' => $unreadTodayCount,
                    'detail' => $unreadTodayCount > 0 ? 'Pendientes de revisar hoy' : 'Todo lo de hoy está revisado',
                    'tone' => 'today',
                    'icon' => $unreadTodayCount > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle',
                ],
                [
                    'label' => 'Sin leer esta semana',
                    'value' => $unreadWeekCount,
                    'detail' => 'Formularios pendientes esta semana',
                    'tone' => 'week',
                    'icon' => 'heroicon-o-calendar-days',
                ],
                [
                    'label' => 'Total sin leer',
                    'value' => $totalUnreadCount,
                    'detail' => 'Todos los formularios pendientes',
                    'tone' => 'total',
                    'icon' => 'heroicon-o-document-text',
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        // Solo mostrar a usuarios que sean super_admin
        $user = Auth::user();

        return $user && static::shieldCanView() && $user->hasRole('super_admin');
    }
}
