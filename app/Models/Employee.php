<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $hidden = ['user_id','model_origen','model_origen_id','owner_id','created_at','updated_at'];
    protected $fillable = ['work_id','dni','first_name','last_name','phone','user_id','model_origen','model_origen_id','fecha_vencimiento_seguro','owner_id'];

    public function work()
    {
        return $this->belongsTo(Works::class);
    }

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','Employee');
    }

    public function trabajos()
    {
        return $this->hasMany(Trabajos::class,'trabajo_id');
    }



    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','Employee')->latest();
    }

    public function isVencidoSeguro()
    {
        if(!$this->fecha_vencimiento_seguro){
            return false;
        }
        return Carbon::parse($this->fecha_vencimiento_seguro) < now() ? true : false;
    }

    public function vencidosFile()
    {
        if (!$this->files || $this->files->isEmpty()) {
            return null;
        }

        $files = $this->files
            ->filter(function ($file) {
                return Carbon::parse($file->fecha_vencimiento)->isPast();
            })
            ->pluck('name')
            ->toArray();

        return !empty($files) ? $files : null;
    }

    public function horarios()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function files()
    {
        return $this->hasMany(EmployeeFile::class);
    }
}
