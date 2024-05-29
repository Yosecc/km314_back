<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitiesAuto extends Model
{
    use HasFactory;

    protected $fillable = ['activities_id','auto_id'];

    // protected $table = 'activities_activities_auto';
}
