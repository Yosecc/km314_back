<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    protected $fillable = ['work_id','dni','first_name','last_name','phone','user_id'];

    public function work()
    {
        return $this->belongsTo(Works::class);
    }

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','Employee');
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','Employee')->latest();
    }
}
