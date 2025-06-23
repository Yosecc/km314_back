<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormIncidentType extends Model
{
    use HasFactory, SoftDeletes;

    public function questions()
    {
        return $this->hasMany(FormIncidentQuestion::class);
    }

    public function responses()
    {
        return $this->hasMany(FormIncidentResponse::class);
    }
}
