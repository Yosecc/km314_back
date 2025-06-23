<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormIncidentQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question',
        'type',
        'options',
        'required',
        'order',
    ];

    public function types()
    {
        return $this->belongsToMany(FormIncidentType::class, 'form_incident_question_type');
    }
    public function categories()
    {
        // Especifico los nombres personalizados de las columnas de la tabla pivote
        return $this->belongsToMany(
            FormIncidentCategoryQuestion::class,
            'form_incident_question_category_question',
            'question_id', // columna local en la pivote
            'category_id'  // columna relacionada en la pivote
        );
    }
}
