<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    protected function afterCreate(): void
    {
        try {
            $recipient = User::whereHas("roles", function($q){ $q->whereIn("name", ["security","super_admin"]); })->get();

            Notification::make()
                ->title('Se ha creado una nueva incidencia')
                ->sendToDatabase($recipient);

        } catch (\Throwable $th) {
        //throw $th;
        }
    }


}
