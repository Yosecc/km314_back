<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormIncidentCategoryQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function questions()
    {
        return $this->belongsToMany(FormIncidentQuestion::class, 'form_incident_question_category_question');
    }
}
