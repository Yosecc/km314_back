<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitiesPeople extends Model
{
    use HasFactory;

    // protected $table = 'activities_activities_people';

    protected $fillable = ['activities_id','model','model_id','type'];

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
         return $value === 'FormControl' ? 'FormControlPeople' : $value;
     }

     public function getPeople()
     {

        // dd($this->model);
        if($this->model == 'FormControl' || $this->model == 'FormControlPeople'){
            return $this->formControlPeople;
        }
        if($this->model == 'Owner'){
            return $this->owner;
        }

        if($this->model == 'Employee'){
            return $this->employee;
        }

        if($this->model == 'OwnerFamily'){
            return $this->ownerFamily;
        }

        if($this->model == 'OwnerSpontaneousVisit'){
            return $this->ownerSpontaneousVisit;
        }


     }

     public function ownerSpontaneousVisit()
     {
        return $this->belongsTo(OwnerSpontaneousVisit::class,'model_id')->with(['owner']);
     }
     public function formControlPeople()
     {
        return $this->belongsTo(FormControlPeople::class,'model_id');
     }
     public function owner()
     {
        return $this->belongsTo(Owner::class,'model_id');
     }
     public function employee()
     {
        return $this->belongsTo(Employee::class,'model_id');
     }
     public function ownerFamily()
     {
        return $this->belongsTo(OwnerFamily::class,'model_id');
     }

}
