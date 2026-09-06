<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activities extends Model
{
    use HasFactory;

    protected $fillable = ['lote_ids','form_control_id','proveedor_id','tipo_entrada','type','observations'];

    protected $casts = [
        // 'tipo_entrada' => 'array',
    ];

    public function peoples()
    {
        return $this->hasMany(ActivitiesPeople::class);
    }

    public function autos()
    {
        return $this->hasMany(ActivitiesAuto::class);
    }


    public function formControl()
    {
        return $this->belongsTo(FormControl::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function formControls()
    {
        return $this->belongsToMany(FormControl::class, 'activity_form_control', 'activity_id', 'form_control_id')
            ->withTimestamps();
    }


}
