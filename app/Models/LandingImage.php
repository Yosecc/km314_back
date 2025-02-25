<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingImage extends Model
{
    use HasFactory;

    protected $fillable = ['img','landing_id'];
}
