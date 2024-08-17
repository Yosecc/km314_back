<?php

namespace App\Models;

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
    
    
}
