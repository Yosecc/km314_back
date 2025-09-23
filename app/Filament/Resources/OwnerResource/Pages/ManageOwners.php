<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageOwners extends ManageRecords
{
    protected static string $resource = OwnerResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Bloquear solo esta página para users con rol 'owner'
        if (auth()->user()->hasRole('owner')) {
            abort(403, 'No tienes permisos para acceder a esta página');
        }
    }

    // Corregir la signatura del método
    public static function canAccess(array $parameters = []): bool
    {
        return !auth()->user()->hasRole('owner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
