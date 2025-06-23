<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormIncidentQuestion extends Model
{
    use HasFactory, SoftDeletes;

    public function type()
    {
        return $this->belongsTo(FormIncidentType::class, 'form_incident_type_id');
    }

    public function category()
    {
        return $this->belongsTo(FormIncidentCategoryQuestion::class, 'form_incident_category_question_id');
    }
}
