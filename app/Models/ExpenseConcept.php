<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseConcept extends Model
{
    use HasFactory;
    
    protected $fillable = ['name','status'];

    public function expenseConceptLoteType()
    {
        return $this->hasMany(ExpenseConceptLoteType::class);
    }
}
