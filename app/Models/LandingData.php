<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingData extends Model
{
    use HasFactory;

    protected $fillable = ['data','landing_id'];

    
}
