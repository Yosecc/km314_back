<?php

namespace App\Services;

use App\Models\ActivitiesPeople;
use Illuminate\Support\Collection;

class AccessPeopleInsideService
{
    public const ALL = 'all';

    public static function categories(): array
    {
        return [
            'spontaneous' => [
                'label' => 'Visitantes espontáneos',
                'short_label' => 'Espontáneos',
                'icon' => 'heroicon-o-user-plus',
            ],
            'owners' => [
                'label' => 'Propietarios en el barrio',
                'short_label' => 'Propietarios',
                'icon' => 'heroicon-o-home-modern',
            ],
            'families' => [
                'label' => 'Familiares en el barrio',
                'short_label' => 'Familiares',
                'icon' => 'heroicon-o-user-group',
            ],
            'employees' => [
                'label' => 'Empleados en el barrio',
                'short_label' => 'Empleados',
                'icon' => 'heroicon-o-identification',
            ],
            'common_visitors' => [
                'label' => 'Visitantes en accesos comunes',
                'short_label' => 'Accesos comunes',
                'detail' => 'Entrada general · Club playa · Club House',
                'icon' => 'heroicon-o-building-office-2',
            ],
            'tenants' => [
                'label' => 'Inquilinos en lotes',
                'short_label' => 'Inquilinos',
                'icon' => 'heroicon-o-key',
            ],
            'workers' => [
                'label' => 'Trabajadores de formularios',
                'short_label' => 'Trabajadores',
                'icon' => 'heroicon-o-wrench-screwdriver',
            ],
            'visits' => [
                'label' => 'Visitas en lotes',
                'short_label' => 'Visitas',
                'icon' => 'heroicon-o-users',
            ],
            'providers' => [
                'label' => 'Personal de proveedores',
                'short_label' => 'Proveedores',
                'icon' => 'heroicon-o-truck',
            ],
            'unclassified' => [
                'label' => 'Otros accesos o registros incompletos',
                'short_label' => 'Sin clasificar',
                'detail' => 'Registros que necesitan identificación',
                'icon' => 'heroicon-o-question-mark-circle',
            ],
        ];
    }

    public static function rows(): Collection
    {
        return CurrentPeopleInsideQuery::make()
            ->with('formControlPeople.formControl')
            ->get();
    }

    public static function categoryKeys(ActivitiesPeople $row): array
    {
        return match ((string) $row->getRawOriginal('model')) {
            'OwnerSpontaneousVisit' => ['spontaneous'],
            'Owner' => ['owners'],
            'OwnerFamily' => ['families'],
            'Employee' => ['employees'],
            'FormControl', 'FormControlPeople' => self::formControlCategoryKeys($row),
            'ProveedorEmpleado' => ['providers'],
            default => ['unclassified'],
        };
    }

    public static function counts(Collection $rows): array
    {
        return collect(self::categories())
            ->mapWithKeys(fn (array $category, string $key) => [
                $key => $rows->filter(
                    fn (ActivitiesPeople $row) => in_array($key, self::categoryKeys($row), true)
                )->count(),
            ])
            ->all();
    }

    private static function formControlCategoryKeys(ActivitiesPeople $row): array
    {
        $form = $row->formControlPeople?->formControl;

        if (! $form) {
            return ['unclassified'];
        }

        $accessTypes = collect($form->access_type)
            ->map(fn ($value) => mb_strtolower((string) $value));
        $incomeTypes = collect($form->income_type)
            ->map(fn ($value) => mb_strtolower((string) $value));
        if ($accessTypes->contains('lote') && $incomeTypes->contains('trabajador')) {
            return ['workers'];
        }

        if ($accessTypes->contains('lote') && $incomeTypes->contains('inquilino')) {
            return ['tenants'];
        }

        if ($accessTypes->contains('lote') && $incomeTypes->contains(
            fn (string $value) => str_contains($value, 'visita')
        )) {
            return ['visits'];
        }

        if ($accessTypes->intersect(['general', 'playa', 'house', 'hause'])->isNotEmpty()) {
            return ['common_visitors'];
        }

        return ['unclassified'];
    }
}
