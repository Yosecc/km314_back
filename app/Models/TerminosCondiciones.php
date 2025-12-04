<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminosCondiciones extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'contenido'];
}
