<?php

namespace App\Traits;

use Filament\Actions\Action;

trait HasQrCodeAction
{
    protected function getQrCodeAction(): Action
    {
        return Action::make('show_qr')
            ->label('Ver código QR')
            ->icon('heroicon-o-qr-code')
            ->color('info')
            ->modalHeading('Código QR de Acceso Rápido')
            ->modalWidth('lg')
            ->modalContent(fn ($record) => view('components.qr-modal', [
                'record' => $record,
                'entityType' => $this->getEntityTypeName($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    protected function getEntityTypeName($record): string
    {
        $modelClass = get_class($record);
        
        return match($modelClass) {
            \App\Models\Employee::class => 'Empleado',
            \App\Models\Owner::class => 'Propietario',
            \App\Models\FormControl::class => 'Formulario de Control',
            default => 'Registro'
        };
    }
}
