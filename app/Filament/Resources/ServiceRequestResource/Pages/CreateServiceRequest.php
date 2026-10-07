<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\Lote;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = Service::with('serviceRequestType')->findOrFail($data['service_id']);
        $data['service_request_type_id'] = $service->service_request_type_id;
        $data['model'] = $service->model;
        $data['user_id'] = Auth::id();
        $data['starts_at'] = $data['starts_at'] ?? now();

        if (ServiceRequestResource::isOwnerContext()) {
            $data['owner_id'] = Auth::user()->owner_id;
            abort_unless(Lote::query()->whereKey($data['lote_id'])->where('owner_id', $data['owner_id'])->exists(), 403);
            $data['service_request_status_id'] = ServiceRequestStatus::defaultPending()->id;
        } else {
            $data['service_request_status_id'] ??= ServiceRequestStatus::defaultPending()->id;
        }

        if ($service->serviceRequestType?->isCalendar && empty($data['ends_at'])) {
            $data['ends_at'] = Carbon::parse($data['starts_at'])->addHour();
        }

        unset($data['is_calendar'], $data['requires_start'], $data['requires_end']);

        return $data;
    }

    protected function beforeCreate(): void
    {
        $service = Service::with('serviceRequestType')->findOrFail($this->data['service_id']);
        if (! $service->serviceRequestType?->isCalendar) {
            return;
        }

        $start = Carbon::parse($this->data['starts_at'] ?? now());
        $end = Carbon::parse($this->data['ends_at'] ?? $start->copy()->addHour());

        if ($end->lessThanOrEqualTo($start)) {
            Notification::make()->title('La fecha de finalización debe ser posterior al inicio')->danger()->send();
            $this->halt();
        }

        if (! empty($service->model) && ! empty($this->data['model_id']) && ! ServiceRequest::isAvailable(
            $start->toDateTimeString(), $end->toDateTimeString(), $service->service_request_type_id,
            (int) $this->data['model_id'], $service->model,
        )) {
            Notification::make()->title('Ese horario ya está reservado')->body('Elegí otra fecha u horario para continuar.')->danger()->send();
            $this->halt();
        }
    }
}
