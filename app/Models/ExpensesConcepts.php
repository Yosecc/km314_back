<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensesConcepts extends Model
{
    use HasFactory;

    protected $fillable = ['expense_concept_id','expense_id','amount','description','lote_id','is_metro_cuadrado','xmetro'];
    
    public function expenseConcept()
    {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }


    

}
