<?php

namespace App\Filament\Resources\FormControlResource\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Pages\FormControlMonitor;
use App\Models\FormControl;
use Filament\Widgets\Widget;

class FormControlStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.resources.form-control-resource.widgets.form-control-stats';

    protected static ?int $sort = -9;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Formularios de Control';
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $monthStart = now('America/Argentina/Buenos_Aires')->startOfMonth();
        $monthEnd = now('America/Argentina/Buenos_Aires')->endOfMonth();
        $forms = FormControl::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->when($user->hasRole('owner'), fn ($query) => $query->where('owner_id', $user->owner_id))
            ->when(! $user->hasRole('owner'), fn ($query) => $query->where('status', '!=', 'OwnerPending'))
            ->get()
            ->map(fn (FormControl $form) => $form->statusComputed());

        $counts = $forms->countBy();
        $ownerPending = $counts->get('OwnerPending', 0);
        $pending = $counts->get('Pending', 0);
        $authorized = $counts->get('Authorized', 0);
        $denied = $counts->get('Denied', 0);
        $overdue = $counts->get('Vencido', 0);
        $expired = $counts->get('Expirado', 0);
        $includeDeniedInAttention = ! $user->hasAnyRole(['super_admin', 'admin', 'Administrador']);
        $attention = $overdue + $expired + ($includeDeniedInAttention ? $denied : 0);
        $attentionDetail = ($includeDeniedInAttention ? "{$denied} rechazados · " : '')."{$overdue} vencidos · {$expired} expirados";
        $canOpenMonitor = FormControlMonitor::canAccess();
        $cards = [];

        if ($user->hasRole('owner')) {
            $cards[] = ['label' => 'Esperan tu aprobación', 'value' => $ownerPending, 'status' => 'OwnerPending', 'tone' => 'approval', 'icon' => 'heroicon-o-user-circle'];
        }

        array_push($cards,
            ['label' => 'Pendientes', 'value' => $pending, 'status' => 'Pending', 'tone' => 'pending', 'icon' => 'heroicon-o-clock'],
            ['label' => 'Autorizados', 'value' => $authorized, 'status' => 'Authorized', 'tone' => 'authorized', 'icon' => 'heroicon-o-check-circle'],
            ['label' => 'Requieren atención', 'value' => $attention, 'status' => 'attention', 'tone' => 'attention', 'icon' => 'heroicon-o-exclamation-triangle', 'detail' => $attentionDetail],
        );

        return ['canOpenMonitor' => $canOpenMonitor, 'monitorUrl' => FormControlMonitor::getUrl(), 'cards' => $cards];
    }
}
