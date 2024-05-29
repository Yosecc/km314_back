<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlLote extends Model
{
    use HasFactory;
    
    protected $fillable = ['form_control_id', 'lotes_id'];

    public function lote()
    {
        return $this->hasOne(Lote::class);
    }
}
