<?php

namespace App\Services;

use App\Models\Activities;
use App\Models\FormControl;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProveedorAccessService
{
    public function resolveProviderByCode(string $code): ?Proveedor
    {
        $proveedor = Proveedor::query()->where('quick_access_code', $code)->first();

        if ($proveedor) {
            return $proveedor;
        }

        return FormControl::query()
            ->where('quick_access_code', $code)
            ->whereNotNull('proveedor_id')
            ->first()
            ?->proveedor;
    }

    /** @return Collection<int, FormControl> */
    public function activeForms(Proveedor $proveedor, ?Carbon $at = null): Collection
    {
        $at ??= Carbon::now();

        return $proveedor->formControls()
            ->where('status', 'Authorized')
            ->with('dateRanges')
            ->get()
            ->filter(fn (FormControl $formControl): bool => $this->isValidAt($formControl, $at))
            ->values();
    }

    /** @return array<int, string> */
    public function authorizedLotes(Collection $forms): array
    {
        return $forms
            ->flatMap(fn (FormControl $formControl) => $formControl->lote_ids ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function openEntry(Proveedor $proveedor, int $empleadoId, ?Carbon $at = null): ?Activities
    {
        $at ??= Carbon::now();

        $ultimoMovimiento = Activities::query()
            ->where('proveedor_id', $proveedor->id)
            ->whereDate('created_at', $at->toDateString())
            ->whereHas('peoples', function ($query) use ($empleadoId): void {
                $query->where('model', 'ProveedorEmpleado')
                    ->where('model_id', $empleadoId);
            })
            ->latest('id')
            ->first();

        return $ultimoMovimiento?->type === 'Entry' ? $ultimoMovimiento : null;
    }

    private function isValidAt(FormControl $formControl, Carbon $at): bool
    {
        if ($formControl->dateRanges->isNotEmpty()) {
            return $formControl->dateRanges->contains(function ($range) use ($at): bool {
                $start = Carbon::parse("{$range->start_date_range} {$range->start_time_range}");

                if ($range->date_unilimited) {
                    return $at->greaterThanOrEqualTo($start);
                }

                if (!$range->end_date_range || !$range->end_time_range) {
                    return false;
                }

                $end = Carbon::parse("{$range->end_date_range} {$range->end_time_range}");

                return $at->betweenIncluded($start, $end);
            });
        }

        if (!$formControl->start_date_range || !$formControl->start_time_range) {
            return false;
        }

        $start = Carbon::parse("{$formControl->start_date_range} {$formControl->start_time_range}");

        if ($formControl->date_unilimited) {
            return $at->greaterThanOrEqualTo($start);
        }

        if (!$formControl->end_date_range || !$formControl->end_time_range) {
            return false;
        }

        $end = Carbon::parse("{$formControl->end_date_range} {$formControl->end_time_range}");

        return $at->betweenIncluded($start, $end);
    }
}
