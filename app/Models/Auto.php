<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auto extends Model
{
    use HasFactory;

    protected $fillable = ['marca', 'patente','modelo','color','user_id','model','model_id'];

    protected $hidden = ['user_id','model','model_id','created_at','updated_at'];

}
