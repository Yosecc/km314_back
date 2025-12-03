<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Actions\Action as NotificationAction;

class CreateFormControl extends CreateRecord
{
    protected static string $resource = FormControlResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Las relaciones (peoples, autos, mascotas, files) se guardan automáticamente
        // después del create cuando usas ->relationship() en los Repeaters
        
        // Aquí solo manejas los datos del modelo principal
        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            // Aquí ya tienes acceso al registro creado con todas sus relaciones
            $formControl = $this->record;
            
            // Puedes acceder a las relaciones guardadas:
            // $formControl->peoples
            // $formControl->autos
            // $formControl->mascotas
            // $formControl->files

            $recipient = User::whereHas("roles", function($q){ 
                $q->whereIn("name", ["super_admin", "admin"]); 
            })->get();

            if($formControl['income_type'] == 'Visita Temporal (24hs)'){

                $formControl->estatus = 'Authorized';
                $formControl->save();

                Notification::make()
                    ->title('Se ha creado un nuevo formulario de control')
                    ->body('Se ha aprobado automáticamente el formulario de control para la visita espontánea 24hs.')
                    ->actions([
                            NotificationAction::make('Ver Formulario')
                                ->button()
                                ->url(route('filament.admin.resources.form-controls.view', $formControl), shouldOpenInNewTab: true)
                        ])
                    ->sendToDatabase($recipient);

                Notification::make()
                            ->title('Se ha aprobado automáticamente el formulario de control para la visita espontánea 24hs.')
                            ->success()
                            ->send();
                return;
            }
            
            
            Notification::make()
                ->title('Se ha creado un nuevo formulario de control')
                 ->actions([
                    NotificationAction::make('Ver Formulario')
                        ->button()
                        ->url(route('filament.admin.resources.form-controls.view', $formControl), shouldOpenInNewTab: true)
                ])
                ->sendToDatabase($recipient);

        } catch (\Throwable $th) {
            // Manejo de errores
        }
    }
}
