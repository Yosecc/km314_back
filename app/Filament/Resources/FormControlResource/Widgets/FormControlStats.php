<?php

namespace App\Filament\Resources\FormControlResource\Widgets;

use App\Models\FormControl;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FormControlStats extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $num = FormControl::where('owner_id', Auth::user()->owner_id)->orderBy('created_at', 'desc')->count();
        return [
            Stat::make('Mis Formularios',$num)
                ->icon('heroicon-o-document-text')
                ->description('Crear formulario')
                ->descriptionIcon('heroicon-o-link')
                ->url('form-controls/create'),
            Stat::make('Mi Perfil', 'Ver')
                ->icon('heroicon-o-user')
                ->description('Ver perfil')
                ->descriptionIcon('heroicon-o-link')
                ->url(route('filament.admin.resources.owners.view-profile-owner', ['record' => auth()->user()->owner_id])),
        ];
    }
}
