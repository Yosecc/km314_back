<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditEmployee extends EditRecord
{
    use HasNotesAction;
    
    protected static string $resource = EmployeeResource::class;

    protected function beforeFill(): void
    {
     //  dd('este es');
    }

    protected function afterSave(): void
    {
        // Si es un owner y no está asociado, asociarlo
        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            if (!$this->record->owners()->where('owner_id', Auth::user()->owner_id)->exists()) {
                $this->record->owners()->attach(Auth::user()->owner_id);
            }
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            // Botón de notificaciones
            self::getNotesPageAction(),
            
            // Acción para aprobar (solo si es admin y el empleado está pendiente)
            Actions\Action::make('aprobar')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar trabajador')
                ->modalDescription('¿Estás seguro de que quieres aprobar este trabajador?')
                ->action(function () {
                    $this->record->update(['status' => 'aprobado']);
                    
                    Notification::make()
                        ->title('Trabajador aprobado')
                        ->success()
                        ->send();
                })
                ->visible(function () {
                    return Auth::user()->hasAnyRole(['admin', 'super_admin']) && 
                           $this->record->status === 'pendiente';
                }),

            // Acción para rechazar (solo si es admin y el empleado está pendiente)
            Actions\Action::make('rechazar')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rechazar trabajador')
                ->modalDescription('¿Estás seguro de que quieres rechazar este trabajador?')
                ->action(function () {
                    $this->record->update(['status' => 'rechazado']);
                    
                    Notification::make()
                        ->title('Trabajador rechazado')
                        ->success()
                        ->send();
                })
                ->visible(function () {
                    return Auth::user()->hasAnyRole(['admin', 'super_admin']) && 
                           $this->record->status === 'pendiente';
                }),

            // Acción para eliminar
            Actions\DeleteAction::make(),
        ];
    }


}
