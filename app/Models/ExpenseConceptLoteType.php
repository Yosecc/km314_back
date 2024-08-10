<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseConceptLoteType extends Model
{
    use HasFactory;

    protected $fillable = ['expense_concept_id','lote_type_id','amount'];

}
