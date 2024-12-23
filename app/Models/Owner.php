<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;
    protected $fillable = ['cuit','number','piso','dto','first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'birthdate', 'gender', 'profile_picture','dni','user_id','owner_status_id'];

    protected $with = ['autos','lotes','status'];

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','Owner');
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','Owner')->latest();
    }

    public function user(){
        return $this->hasOne(User::class,'owner_id');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    public function families()
    {
        return $this->hasMany(OwnerFamily::class);
    }

    public function status()
    {
        return $this->belongsTo(OwnerStatus::class,'owner_status_id');
    }

    public function nombres(): string{
        return $this->first_name." ".$this->last_name;
    }
}
