<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerSpontaneousVisit extends Model
{
    use HasFactory;
    protected $fillable = [ 'owner_id','dni','first_name','last_name','email','phone','aprobado','agregado','salida' ];

    public function scopeDni($query, $val)
    {
        if($val){
            $query->where('dni', 'LIKE' ,'%'.$val.'%');
        }
    }
    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','OwnerSpontaneousVisit')->latest();
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}
