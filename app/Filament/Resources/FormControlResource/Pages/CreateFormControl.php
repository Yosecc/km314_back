<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource\Pages\Concerns\HasPublicFormLinkAction;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\FormControlResource;
use App\Services\ApplicationNotificationService;


class CreateFormControl extends CreateRecord
{
    use HasPublicFormLinkAction;

    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->sharePublicFormAction(),
        ];
    }

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
            $formControl = $this->record;

            if (collect($formControl->income_type)->contains('Visita Temporal (24hs)')) {
                $formControl->update(['status' => 'Authorized']);

                Notification::make()
                    ->title('Formulario autorizado automáticamente')
                    ->success()
                    ->send();
            }

            $isAutomatic = $formControl->status === 'Authorized';
            app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                ['aprobar_form::control', 'rechazar_form::control'],
                $isAutomatic ? 'Nuevo formulario autorizado automáticamente' : 'Nuevo formulario pendiente de aprobación',
                $isAutomatic
                    ? 'El formulario #'.$formControl->id.' corresponde a una visita temporal de 24 horas.'
                    : 'El formulario #'.$formControl->id.' espera la revisión de administración.',
                ['type' => 'form_control', 'form_control_id' => $formControl->id, 'status' => $formControl->status],
                FormControlResource::getUrl('view', ['record' => $formControl]),
                'heroicon-o-document-check',
            );

        } catch (\Throwable $th) {
            report($th);

            Notification::make()
                ->title('El formulario fue creado')
                ->body('No se pudieron enviar todas las notificaciones. El formulario quedó guardado correctamente.')
                ->warning()
                ->send();
        }
    }
}
