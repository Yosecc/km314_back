<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Filament\Notifications\Notification;
use App\Models\ServiceRequest;
class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function beforeCreate(): void
    {

        $isAvailable = ServiceRequest::isAvailable(
            $this->data['starts_at'],
            $this->data['ends_at'],
            $this->data['service_request_type_id'],
            $this->data['model_id'],
            $this->data['model']
        );

        if (!$isAvailable) {
            Notification::make()
                ->title('Reservación no está disponible')
                ->danger()
                ->send();
                $this->halt();
        } else {
            // Notification::make()
            //     ->title('Reservación disponible')
            //     ->success()
            //     ->send();
        }
    }

    protected function afterCreate(): void
    {
        $recipient = User::whereHas("roles", function($q){ $q->where("name", "super_admin"); })->get();

        Notification::make()
            ->title('Nueva solicitud')
            ->sendToDatabase($recipient);
    }
}
