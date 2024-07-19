<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory;
    protected $fillable = ['lote_id','propertie_id','owner_id','expense_status_id','date_prox_payment'];


    public function expenseStatus()
    {
        return $this->belongsTo(ExpenseStatus::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function concepts()
    {
        return $this->hasMany(ExpensesConcepts::class);
    }

    public function propertie()
    {
        return $this->belongsTo(Property::class);
    }
    


}
