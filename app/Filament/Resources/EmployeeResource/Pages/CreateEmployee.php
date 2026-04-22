<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function mount(): void
    {
        parent::mount();

        if ($companieId = request()->query('companie_id')) {
            $this->data['employeeOrigens'] = [
                [
                    'model' => 'ConstructionCompanie',
                    'model_id' => (int) $companieId,
                ],
            ];
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Siempre establecer fecha_vencimiento_seguro
        $data['fecha_vencimiento_seguro'] = Carbon::now()->addMonths(3)->toDateString();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Si es un owner, asociarlo automáticamente
        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            if (!$this->record->owners()->where('owner_id', Auth::user()->owner_id)->exists()) {
                $this->record->owners()->attach(Auth::user()->owner_id);
            }
        }
    }
}
