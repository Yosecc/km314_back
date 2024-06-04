<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlPeople extends Model
{
    use HasFactory;

    protected $fillable = ['form_control_id', 'dni', 'first_name', 'last_name', 'phone', 'is_responsable', 'is_acompanante', 'is_menor'];

    public function formControl()
    {
        return $this->belongsTo(FormControl::class);
    
    }
}
