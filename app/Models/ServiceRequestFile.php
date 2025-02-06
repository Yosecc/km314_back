<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestFile extends Model
{
    use HasFactory;

    protected $hidden = ['created_at','updated_at','user_id','service_request_id'];

    protected $fillable = ['service_request_id','user_id','file','description','attachment_file_names'];
}
