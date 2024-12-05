<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentNote extends Model
{
    use HasFactory;

    protected $fillable = ['incident_id','description','status','user_id','file'];
}
