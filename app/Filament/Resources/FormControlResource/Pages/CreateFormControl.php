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
