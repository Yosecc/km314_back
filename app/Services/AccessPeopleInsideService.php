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
            'Employee' => ['employees'],
            'FormControl', 'FormControlPeople' => self::formControlCategoryKeys($row),
            default => [],
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
            return [];
        }

        $accessTypes = collect($form->access_type)
            ->map(fn ($value) => mb_strtolower((string) $value));
        $incomeTypes = collect($form->income_type)
            ->map(fn ($value) => mb_strtolower((string) $value));
        $categories = [];

        if ($accessTypes->intersect(['general', 'playa', 'house', 'hause'])->isNotEmpty()) {
            $categories[] = 'common_visitors';
        }

        if ($accessTypes->contains('lote') && $incomeTypes->contains('inquilino')) {
            $categories[] = 'tenants';
        }

        if ($accessTypes->contains('lote') && $incomeTypes->contains('trabajador')) {
            $categories[] = 'workers';
        }

        if ($accessTypes->contains('lote') && $incomeTypes->contains(
            fn (string $value) => str_contains($value, 'visita')
        )) {
            $categories[] = 'visits';
        }

        return $categories;
    }
}
