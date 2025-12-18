<?php

namespace App\Filament\Widgets;

use App\Services\FormIncidentComplianceService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class FormIncidentComplianceWidget extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.form-incident-compliance-widget';

    protected int | string | array $columnSpan = 'full';

     protected static ?int $sort = -97;

    protected static ?string $heading = 'Formularios de Incidentes';


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

    /**
     * Genera la URL para completar un formulario específico
     */
    public function getCompleteFormUrl(int $formTypeId): string
    {
        return route('filament.admin.resources.form-incident-responses.create', [
            'form_incident_type_id' => $formTypeId
        ]);
    }

    public static function canView(): bool
    {
        // Solo mostrar si el usuario tiene formularios obligatorios asignados
        $user = Auth::user();
        return $user && $user->formIncidentRequirements()->active()->exists();
    }
}
