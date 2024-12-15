<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function beforeCreate(): void
    {

        $SRtype = ServiceRequestType::find($this->data['service_request_type_id']);

        if(!$SRtype->isCalendar){
            return;
        }

        $isAvailable = ServiceRequest::isAvailable(
            $this->data['starts_at'],
            $this->data['ends_at'],
            $this->data['service_request_type_id'],
            $this->data['model_id'],
            $this->data['model']
        );

        if (!$isAvailable) {
            Notification::make()
                ->title('Fecha de la reservación no está disponible')
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
