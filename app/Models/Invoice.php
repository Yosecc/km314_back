<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'owner_id',
        'lote_id',
        'period',
        'total',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'invoice_payment');
    }
}
