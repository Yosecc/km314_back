<?php

namespace App\Filament\Resources\FormControlResource\Widgets;

use App\Models\FormControl;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatusOverview extends BaseWidget
{
     public ?FormControl $record = null;

    protected static ?string $heading = 'Estado del Formulario de Control';

     
    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $estadoTexto = match($this->record->status) {
            'Authorized' => 'Autorizado',
            'Pending' => 'Pendiente',
            'Denied' => 'Denegado',
            default => $this->record->status
        };

        $color = match($this->record->status) {
            'Authorized' => 'success',
            'Pending' => 'warning',
            'Denied' => 'danger',
            default => 'gray'
        };

        $icon = match($this->record->status) {
            'Authorized' => 'heroicon-o-check-circle',
            'Pending' => 'heroicon-o-clock',
            'Denied' => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle'
        };

        // Verificar si está vencido o expirado
        if ($this->record->isVencido()) {
            $estadoTexto = 'Vencido';
            $color = 'danger';
            $icon = 'heroicon-o-exclamation-triangle';
        } elseif ($this->record->isExpirado()) {
            $estadoTexto = 'Expirado';
            $color = 'danger';
            $icon = 'heroicon-o-calendar-days';
        }

        return [
            Stat::make('Estado del Formulario', $estadoTexto)
                // ->description($this->record->owner ? 'Propietario: ' . $this->record->owner->first_name . ' ' . $this->record->owner->last_name : 'Sin propietario')
                ->icon($icon)
                ->color($color),
        ];
    }
}
