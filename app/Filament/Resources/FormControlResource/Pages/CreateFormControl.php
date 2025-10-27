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
        if (isset($data['peoples']) && is_array($data['peoples'])) {
            $data['peoples'] = array_map(function ($person) {
                $person['is_responsable'] = isset($person['is_responsable']) ? (bool)$person['is_responsable'] : false;
                $person['is_acompanante'] = isset($person['is_acompanante']) ? (bool)$person['is_acompanante'] : false;
                $person['is_menor'] = isset($person['is_menor']) ? (bool)$person['is_menor'] : false;
                return $person;
            }, $data['peoples']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            $recipient = User::whereHas("roles", function($q){ $q->whereIn("name", ["super_admin"]); })->get();

            Notification::make()
                ->title('Se ha creado un nuevo formulario de control')
                ->sendToDatabase($recipient);

        } catch (\Throwable $th) {

        //throw $th;
        }
    }
}
