<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'status' => 'boolean',
        'isDateInicio' => 'boolean',
        'isDateFin' => 'boolean',
    ];
    protected $fillable = ['service_type_id','name','amount','color','model','service_request_type_id','terminos','order','status','isDateInicio','isDateFin'];
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
