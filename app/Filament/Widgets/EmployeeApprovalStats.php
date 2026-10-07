<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use Filament\Widgets\Widget;

class EmployeeApprovalStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.employee-approval-stats';

    protected static ?int $sort = -6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Gestión de Trabajadores';
    }

    public function getViewData(): array
    {
        $base = Employee::query();

        if (auth()->user()->hasRole('owner')) {
            $ownerId = auth()->user()->owner_id;
            $base->where(function ($query) use ($ownerId): void {
                $query->where('owner_id', $ownerId)
                    ->orWhereHas('owners', fn ($owners) => $owners->where('owner_id', $ownerId));
            });
        }

        $pending = (clone $base)->where('status', 'pendiente')->count();
        $approved = (clone $base)->where('status', 'aprobado')->count();
        $rejected = (clone $base)->where('status', 'rechazado')->count();
        $total = (clone $base)->count();

        return [
            'canManage' => EmployeeResource::canViewAny(),
            'resourceUrl' => EmployeeResource::getUrl('index'),
            'hasPending' => $pending > 0,
            'cards' => [
                ['label' => 'Pendientes de aprobación', 'value' => $pending, 'status' => 'pendiente', 'tone' => 'pending', 'icon' => 'heroicon-o-exclamation-triangle', 'detail' => $pending === 1 ? 'Requiere revisión' : 'Requieren revisión'],
                ['label' => 'Aprobados', 'value' => $approved, 'status' => 'aprobado', 'tone' => 'approved', 'icon' => 'heroicon-o-check-circle'],
                ['label' => 'Rechazados', 'value' => $rejected, 'status' => 'rechazado', 'tone' => 'rejected', 'icon' => 'heroicon-o-x-circle'],
                ['label' => 'Total registrados', 'value' => $total, 'status' => null, 'tone' => 'total', 'icon' => 'heroicon-o-user-group'],
            ],
        ];
    }
}
