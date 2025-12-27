<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlPeople extends Model
{
    use HasFactory;

    protected $fillable = ['form_control_id', 'dni', 'first_name', 'last_name', 'phone', 'is_responsable', 'is_acompanante', 'is_menor', 'file_dni'];

    protected $casts = [
        'is_responsable' => 'boolean',
        'is_acompanante' => 'boolean',
        'is_menor' => 'boolean',
    ];

    public function formControl()
    {
        return $this->belongsTo(FormControl::class);

    }

    public function files()
    {
        return $this->hasMany(FormControlPeopleFile::class);
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','FormControl')->latest();
    }
}
