<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Pages\InterestedMonitor;
use App\Models\InterestedFollowUp;
use Filament\Widgets\Widget;

class InterestedFollowUpStats extends Widget
{
    use HasWidgetShield {
        canView as protected canViewWidget;
    }

    protected static string $view = 'filament.widgets.interested-follow-up-stats';

    protected static ?int $sort = -9;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::canViewWidget() && (auth()->user()?->can('view_any_interested::follow::up') ?? false);
    }

    public function getHeading(): string
    {
        return 'Seguimiento de interesados';
    }

    public function getViewData(): array
    {
        $base = InterestedFollowUp::query()->pending()->where('assigned_user_id', auth()->id());

        return [
            'monitorUrl' => InterestedMonitor::getUrl(),
            'canOpenMonitor' => InterestedMonitor::canAccess(),
            'cards' => [
                ['label' => 'Para hoy', 'value' => (clone $base)->whereDate('due_at', today())->count(), 'filter' => 'today', 'tone' => 'today'],
                ['label' => 'Vencidos', 'value' => (clone $base)->where('due_at', '<', now())->count(), 'filter' => 'overdue', 'tone' => 'overdue'],
                ['label' => 'Próximos 7 días', 'value' => (clone $base)->whereBetween('due_at', [now(), now()->addDays(7)])->count(), 'filter' => 'active', 'tone' => 'upcoming'],
                ['label' => 'Total pendientes', 'value' => (clone $base)->count(), 'filter' => 'active', 'tone' => 'pending'],
            ],
        ];
    }
}
