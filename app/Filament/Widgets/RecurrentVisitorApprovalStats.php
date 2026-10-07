<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Filament\Resources\RecurrentVisitorResource;
use App\Models\RecurrentVisitor;
use Filament\Widgets\Widget;

class RecurrentVisitorApprovalStats extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.recurrent-visitor-approval-stats';
    protected static ?int $sort = -7;
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Visitantes Recurrentes';
    }

    public function getViewData(): array
    {
        $base = RecurrentVisitor::query();

        if (auth()->user()->hasRole('owner')) {
            $base->where('owner_id', auth()->user()->owner_id);
        }

        $pending = (clone $base)->where('status', 'pendiente')->count();
        $approved = (clone $base)->where('status', 'aprobado')->count();
        $rejected = (clone $base)->where('status', 'rechazado')->count();
        $total = (clone $base)->count();

        return [
            'canManage' => RecurrentVisitorResource::canViewAny(),
            'resourceUrl' => RecurrentVisitorResource::getUrl('index'),
            'hasPending' => $pending > 0,
            'cards' => [
                ['label'=>'Pendientes de aprobación','value'=>$pending,'status'=>'pendiente','tone'=>'pending','icon'=>'heroicon-o-exclamation-triangle','detail'=>$pending === 1 ? 'Requiere revisión del administrador' : 'Requieren revisión del administrador'],
                ['label'=>'Aprobados','value'=>$approved,'status'=>'aprobado','tone'=>'approved','icon'=>'heroicon-o-check-circle'],
                ['label'=>'Rechazados','value'=>$rejected,'status'=>'rechazado','tone'=>'rejected','icon'=>'heroicon-o-x-circle'],
                ['label'=>'Total registrados','value'=>$total,'status'=>null,'tone'=>'total','icon'=>'heroicon-o-user-group'],
            ],
        ];
    }
}
