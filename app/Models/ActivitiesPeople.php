<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitiesPeople extends Model
{
    use HasFactory;

    // protected $table = 'activities_activities_people';

    protected $fillable = ['activities_id','model','model_id'];

    public function activitie()
    {
        return $this->belongsTo(Activities::class,'activities_id');
    }

    public function model()
    {
        return $this->morphTo();
    }

     // Mutador para modificar el valor de "model" al acceder al atributo
     public function getModelAttribute($value)
     {
         return $value === 'FormtControl' ? 'FormControlPeople' : $value;
     }
}
