<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'owner_id',
        'amount',
        'payment_date',
        'method',
        'notes',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_payment');
    }
}
