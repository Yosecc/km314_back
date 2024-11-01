<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingCampos extends Model
{
    use HasFactory;

    protected $fillable = ['type','name','label','placeholder','landing_id'];
    
}
