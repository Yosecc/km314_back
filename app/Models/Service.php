<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['service_type_id','name','amount','color','model','service_request_type_id','terminos','order'];
    protected $with = ['serviceRequestType','serviceType'];

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function serviceRequestType()
    {
        return $this->belongsTo(ServiceRequestType::class);
    }

}
