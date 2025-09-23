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
            abort(403, 'No tienes permisos para acceder a la lista de propietarios');
        }
    }

    // También puedes usar canAccess si prefieres
    public static function canAccess(): bool
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
