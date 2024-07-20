<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResponsiblePeople extends Model
{
    use HasFactory;

    protected $table = 'service_request_responsible_peoples';

    protected $fillable = ['dni','first_name','last_name','phone'];

}
