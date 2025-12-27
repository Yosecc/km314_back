<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\FormControlResource;
use App\Models\FormControl;

use Filament\Notifications\Actions\Action as NotificationAction;

class CreateFormControl extends CreateRecord
{
    protected static string $resource = FormControlResource::class;

    protected function getFormActions(): array
    {
        $terminosCondiciones = \App\Models\TerminosCondiciones::first();

        return [
            Actions\Action::make('create')
                ->label('Crear formulario')
                ->requiresConfirmation()
                ->modalHeading('Confirmar creación del formulario')
                ->modalDescription('¿Está seguro de que desea crear este formulario de control? Verifique que todos los datos sean correctos.')
                ->modalSubmitActionLabel('Sí, crear formulario')
                ->modalCancelActionLabel('Cancelar')
                ->form([
                    Forms\Components\Checkbox::make('acepta_terminos')
                        ->label($terminosCondiciones->titulo ?? 'Términos y Condiciones')
                        ->helperText(fn () => new \Illuminate\Support\HtmlString(
                            'He leído y acepto los <a href="/terminos-y-condiciones?id=1" target="_blank" class="text-primary-600 hover:underline">términos y condiciones</a>'
                        ))
                        ->accepted()
                        ->validationAttribute('aceptación de términos y condiciones')
                        ->required()
                ])
                ->action(fn (array $data) => $this->create())
        ];
    }

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

            // dd($formControl->income_type);
            if($formControl->income_type === 'Visita Temporal (24hs)'){

                $formControl->status = 'Authorized';
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
            dd($th->getMessage());
        }
    }
}
