<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaEnElBarrio extends Model
{
    use HasFactory;

    protected $table = 'personas_en_el_barrio';
    public $timestamps = false;
    protected $guarded = [];
}
