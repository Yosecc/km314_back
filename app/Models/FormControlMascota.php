<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlMascota extends Model
{
    use HasFactory;
    protected $fillable = ['form_control_id', 'tipo_mascota', 'raza', 'nombre', 'is_vacunado'];

    public function formControl()
    {
        return $this->belongsTo(FormControl::class);
    }
}
