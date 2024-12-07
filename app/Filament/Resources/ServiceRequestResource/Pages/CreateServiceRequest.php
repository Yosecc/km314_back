<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Filament\Notifications\Notification;
class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function afterCreate(): void
    {
        $recipient = User::whereHas("roles", function($q){ $q->where("name", "super_admin"); })->get();

        Notification::make()
            ->title('Nueva solicitud')
            ->sendToDatabase($recipient);
    }
}
