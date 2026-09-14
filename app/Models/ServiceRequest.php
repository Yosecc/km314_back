<?php

namespace App\Models;

use App\Filament\Resources\ServiceRequestResource;
use App\Services\ApplicationNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['alias','name','user_id','starts_at','ends_at','service_request_responsible_people_id','service_request_status_id','service_request_type_id','service_id','lote_id','owner_id','model','model_id','options','observations','asignado_status_id','status_changed_at','status_changed_by','resolution_notes'];

    protected $casts = [
        'options' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $request): void {
            app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                ['view_any_service::request', 'update_service::request'],
                'Nueva solicitud de servicio',
                sprintf('%s · %s', $request->name, $request->lote?->getNombre() ?? 'Sin lote'),
                ['type' => 'service_request', 'service_request_id' => $request->id, 'event' => 'created'],
                ServiceRequestResource::getUrl('edit', ['record' => $request]),
                'heroicon-o-wrench-screwdriver',
            );
        });

        static::updated(function (self $request): void {
            if (! $request->wasChanged('service_request_status_id') || ! $request->owner_id) {
                return;
            }

            app(ApplicationNotificationService::class)->send(
                User::query()->where('owner_id', $request->owner_id)->get(),
                'Tu solicitud cambió de estado',
                sprintf('%s ahora está: %s.', $request->name, $request->serviceRequestStatus?->name ?? 'actualizada'),
                ['type' => 'service_request', 'service_request_id' => $request->id, 'event' => 'status_changed'],
                ServiceRequestResource::getUrl('edit', ['record' => $request]),
                'heroicon-o-wrench-screwdriver',
            );
        });
    }

    public function serviceRequestFile()
    {
        return $this->hasMany(ServiceRequestFile::class);
    }

    public function serviceRequestNote()
    {
        return $this->hasMany(ServiceRequestNote::class);
    }

    public function responsible()
    {
        return $this->belongsTo(ServiceRequestResponsiblePeople::class,'service_request_responsible_people_id');
    }

    public function serviceRequestStatus()
    {
        return $this->belongsTo(ServiceRequestStatus::class);
    }

    public function serviceRequestType()
    {
        return $this->belongsTo(ServiceRequestType::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function userAsignado()
    {
        return $this->belongsTo(User::class, 'asignado_status_id');
    }

    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->hasRole('owner')) {
            return $query;
        }

        return $query->where('owner_id', $user->owner_id);
    }

    public function statusCode(): ?string
    {
        return $this->serviceRequestStatus?->code;
    }

    public function isEditableByOwner(): bool
    {
        return $this->statusCode() === ServiceRequestStatus::PENDING;
    }

    public function transitionTo(ServiceRequestStatus $status, ?User $actor = null, ?string $notes = null): void
    {
        $this->update([
            'service_request_status_id' => $status->id,
            'status_changed_at' => now(),
            'status_changed_by' => $actor?->id,
            'resolution_notes' => $notes,
        ]);
    }

     /**
     * Valida la disponibilidad de una reservación entre dos fechas.
     *
     * @param string $startDateTime Fecha y hora de inicio seleccionada.
     * @param string $endDateTime Fecha y hora de fin seleccionada.
     * @param int $serviceRequestTypeId ID del tipo de solicitud de servicio.
     * @param int $modelId ID del modelo asociado.
     * @param string $model Nombre del modelo asociado.
     * @return bool True si está disponible, False si hay traslapes.
     */
    public static function isAvailable(
        string $startDateTime,
        string $endDateTime,
        int $serviceRequestTypeId,
        int $modelId,
        string $model,
        ?int $ignoreRequestId = null,
    ): bool {
        $selectedStartDateTime = Carbon::parse($startDateTime);
        $selectedEndDateTime = Carbon::parse($endDateTime);

        return !self::where('model', $model)
            ->when($ignoreRequestId, fn (Builder $query) => $query->whereKeyNot($ignoreRequestId))
            ->where('model_id', $modelId)
            ->where('service_request_type_id', $serviceRequestTypeId)
            ->where(function ($query) use ($selectedStartDateTime, $selectedEndDateTime) {
                $query->where(function ($subQuery) use ($selectedStartDateTime, $selectedEndDateTime) {
                    $subQuery->where('starts_at', '<', $selectedEndDateTime)
                             ->where('ends_at', '>', $selectedStartDateTime);
                });
            })
            ->exists();
    }


}
