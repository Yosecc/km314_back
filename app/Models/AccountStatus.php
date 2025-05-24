<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountStatus extends Model
{
    protected $fillable = [
        'owner_id',
        'balance',
        'total_invoiced',
        'total_paid',
        'last_invoice_id',
        'last_payment_id',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function lastInvoice()
    {
        return $this->belongsTo(Invoice::class, 'last_invoice_id');
    }

    public function lastPayment()
    {
        return $this->belongsTo(Payment::class, 'last_payment_id');
    }
}
