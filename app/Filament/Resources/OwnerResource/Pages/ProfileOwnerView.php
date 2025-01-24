<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;


class ProfileOwnerView extends ViewRecord
{
    protected static string $resource = OwnerResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-eye'; // Icono para el menú de navegación
    protected static ?string $navigationLabel = 'Ver perfil'; // Etiqueta para el menú de navegación
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

            ]);
    }

    protected function getShieldPermission(): string
    {
        return 'viewProfileOwner'; // Define el permiso personalizado
    }



}
