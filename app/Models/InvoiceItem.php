<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'amount',
        'is_fixed',
        'expense_concept_id',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function expenseConcept()
    {
        return $this->belongsTo(\App\Models\ExpenseConcept::class);
    }
}
