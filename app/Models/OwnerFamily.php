<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerFamily extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id','dni','first_name','last_name','parentage','is_menor','phone'];

    public function familiarPrincipal()
    {
        return $this->belongsTo(Owner::class,'owner_id');
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','OwnerFamily')->latest();
    }
}
