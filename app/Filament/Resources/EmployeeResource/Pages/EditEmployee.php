<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use App\Services\ApplicationNotificationService;

class EditEmployee extends EditRecord
{
    use HasNotesAction;
    
    protected static string $resource = EmployeeResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Si es owner y el registro está aprobado, redirigir
        if (Auth::user()->hasRole('owner') && $this->record->status === 'aprobado') {
            Notification::make()
                ->title('No se puede editar')
                ->body('No puedes editar un trabajador que ya ha sido aprobado.')
                ->danger()
                ->send();

            $this->redirect(EmployeeResource::getUrl('index'));
        }
    }

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

        // Si es un owner y el estado era rechazado, cambiarlo a pendiente
        if (Auth::user()->hasRole('owner') && $this->record->status === 'rechazado') {
            $this->record->update(['status' => 'pendiente']);

            app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                ['update_employee'],
                'Trabajador actualizado para revisión',
                $this->record->nombres().' fue actualizado y espera una nueva revisión.',
                ['type' => 'employee', 'employee_id' => $this->record->id, 'status' => 'pendiente'],
                EmployeeResource::getUrl('edit', ['record' => $this->record]),
                'heroicon-o-pencil-square',
            );
            
            Notification::make()
                ->title('Estado actualizado')
                ->body('El trabajador ha sido enviado nuevamente para aprobación.')
                ->info()
                ->send();
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

                    $this->notifyOwners(
                        'Tu trabajador fue aprobado',
                        'Ya podés crear un formulario de control para configurar sus accesos y horarios.',
                        'aprobado',
                    );
                    
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

                    $this->notifyOwners(
                        'Tu trabajador fue rechazado',
                        'Revisá el motivo indicado y actualizá la información para volver a enviarlo.',
                        'rechazado',
                    );

                    
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

    private function notifyOwners(string $title, string $body, string $status): void
    {
        $users = ($this->record->owner_id
            ? \App\Models\User::query()->where('owner_id', $this->record->owner_id)->get()
            : collect())
            ->merge($this->record->owners()->with('user')->get()->pluck('user')->filter())
            ->unique('id')
            ->values();

        app(ApplicationNotificationService::class)->send(
            $users,
            $title,
            $body,
            ['type' => 'employee', 'employee_id' => $this->record->id, 'status' => $status],
            EmployeeResource::getUrl('view', ['record' => $this->record]),
            $status === 'aprobado' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
        );
    }


}
