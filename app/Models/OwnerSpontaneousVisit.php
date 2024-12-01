<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerSpontaneousVisit extends Model
{
    use HasFactory;
    protected $fillable = [ 'owner_id','dni','first_name','last_name','email','phone','aprobado','agregado' ];

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','OwnerSpontaneousVisit')->latest();
    }
}
