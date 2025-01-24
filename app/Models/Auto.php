<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Auto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['marca', 'patente','modelo','color','user_id','model','model_id'];

    protected $hidden = ['user_id','model','model_id','created_at','updated_at'];

}
