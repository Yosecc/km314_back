<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['alias','name','user_id','starts_at','ends_at','service_request_responsible_people_id','service_request_status_id','service_request_type_id','service_id','lote_id','owner_id','model','model_id','options','observations'];

    protected $casts = [
        'options' => 'array',
    ];

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
        string $model
    ): bool {
        $selectedStartDateTime = Carbon::parse($startDateTime);
        $selectedEndDateTime = Carbon::parse($endDateTime);

        return !self::where('model', $model)
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
