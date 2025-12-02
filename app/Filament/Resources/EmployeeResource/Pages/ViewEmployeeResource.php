<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use App\Filament\Resources\EmployeeResource\Traits\HasGestionAction;

class ViewEmployeeResource extends ViewRecord
{
    use HasNotesAction, HasGestionAction;

    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Acción para editar
           // Botón de notificaciones
            self::getNotesPageAction(),

            self::getGestionarAutosPageAction(),
            self::getGestionarHorariosPageAction(),
            self::getSolicitarReverificacionPageAction(),
            self::getRenovarDocumentosPageAction(),
            
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

                     if($this->record->owner && $this->record->owner->user ){

                        Notification::make()
                        ->title('Trabajador aprobado.')
                        ->sendToDatabase($this->record->owner->user);
                    }
                    
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
                ->form([
                    Forms\Components\Textarea::make('motivo_rechazo')
                        ->label('Motivo del rechazo')
                        ->placeholder('Escribe el motivo por el cual se rechaza este trabajador...')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => 'rechazado']);
                    
                    // Crear nota con el motivo del rechazo
                    \App\Models\EmployeeNote::create([
                        'description' => 'Motivo del rechazo: ' . $data['motivo_rechazo'],
                        'employee_id' => $this->record->id,
                        'user_id' => Auth::id(),
                        'status' => false, // No leída
                    ]);

                     if($this->record->owner && $this->record->owner->user ){

                        Notification::make()
                        ->title('Trabajador rechazado. Ir a Gestión de trabajadores')
                        ->sendToDatabase($this->record->owner->user);
                    }
                    
                    Notification::make()
                        ->title('Trabajador rechazado')
                        ->body('Se ha creado una notificación con el motivo del rechazo.')
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
