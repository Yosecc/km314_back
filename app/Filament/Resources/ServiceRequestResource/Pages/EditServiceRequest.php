<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use App\Models\Lote;
use App\Models\Service;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function beforeSave(): void
    {
        abort_unless(ServiceRequestResource::canEdit($this->record->fresh()), 403);
        $service = Service::with('serviceRequestType')->findOrFail($this->data['service_id']);
        if (! $service->serviceRequestType?->isCalendar) return;

        $start = Carbon::parse($this->data['starts_at'] ?? now());
        $end = Carbon::parse($this->data['ends_at'] ?? $start->copy()->addHour());
        if ($end->lessThanOrEqualTo($start)) {
            Notification::make()->title('La fecha de finalización debe ser posterior al inicio')->danger()->send();
            $this->halt();
        }

        if (! empty($service->model) && ! empty($this->data['model_id']) && ! ServiceRequest::isAvailable(
            $start->toDateTimeString(), $end->toDateTimeString(), $service->service_request_type_id,
            (int) $this->data['model_id'], $service->model, $this->record->id,
        )) {
            Notification::make()->title('Ese horario ya está reservado')->body('Elegí otra fecha u horario para continuar.')->danger()->send();
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = Service::with('serviceRequestType')->findOrFail($data['service_id']);
        $data['service_request_type_id'] = $service->service_request_type_id;
        $data['model'] = $service->model;
        $data['starts_at'] = $data['starts_at'] ?? $this->record->starts_at ?? now();
        if ($service->serviceRequestType?->isCalendar && empty($data['ends_at'])) $data['ends_at'] = Carbon::parse($data['starts_at'])->addHour();

        if (ServiceRequestResource::isOwnerContext()) {
            $data['owner_id'] = $this->record->owner_id;
            $data['service_request_status_id'] = $this->record->service_request_status_id;
            $data['asignado_status_id'] = $this->record->asignado_status_id;
            $data['resolution_notes'] = $this->record->resolution_notes;
            abort_unless(Lote::query()->whereKey($data['lote_id'])->where('owner_id', $data['owner_id'])->exists(), 403);
        } elseif ((int) ($data['service_request_status_id'] ?? 0) !== (int) $this->record->service_request_status_id) {
            $data['status_changed_at'] = now();
            $data['status_changed_by'] = Auth::id();
        }

        unset($data['is_calendar'], $data['requires_start'], $data['requires_end']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn () => ServiceRequestResource::canDelete($this->record))];
    }
}
