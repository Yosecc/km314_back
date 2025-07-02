<?php

namespace App\Filament\Widgets;

use App\Services\FormIncidentComplianceService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class FormIncidentComplianceWidget extends Widget
{
    protected static string $view = 'filament.widgets.form-incident-compliance-widget';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $complianceService = new FormIncidentComplianceService();
        $user = Auth::user();

        if (!$user) {
            return ['status' => null];
        }

        $status = $complianceService->getComplianceStatusForUser($user);

        return [
            'status' => $status,
            'user' => $user,
        ];
    }

    public static function canView(): bool
    {
        // Solo mostrar si el usuario tiene formularios obligatorios asignados
        $user = Auth::user();
        return $user && $user->formIncidentRequirements()->active()->exists();
    }
}
