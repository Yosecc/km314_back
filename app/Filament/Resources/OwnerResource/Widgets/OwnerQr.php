<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Actions\Action;
use App\Models\Owner;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class OwnerQr extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    
    // protected static string $view = 'filament.widgets.owner-qr';
    
    public ?Owner $record = null;
    
    protected int | string | array $columnSpan = 'full';

    public function showQrAction(): Action
    {
        return Action::make('show_qr')
            ->label('Ver QR')
            ->icon('heroicon-o-qr-code')
            ->color('info')
            ->modalHeading('Código de Acceso Rápido')
            ->modalDescription(fn () => 'Propietario: ' . $this->record?->first_name . ' ' . $this->record?->last_name)
            ->modalContent(fn () => view('components.qr-modal', [
                'record' => $this->record,
                'qrCode' => $this->record?->generateQrCode(),
                'entityType' => 'Propietario'
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }
}
