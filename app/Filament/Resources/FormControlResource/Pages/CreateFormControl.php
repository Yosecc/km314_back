<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

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
                $q->whereIn("name", ["super_admin"]); 
            })->get();

            Notification::make()
                ->title('Se ha creado un nuevo formulario de control')
                ->sendToDatabase($recipient);

        } catch (\Throwable $th) {
            // Manejo de errores
        }
    }
}
