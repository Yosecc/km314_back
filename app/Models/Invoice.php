<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'owner_id',
        'lote_id',
        'period',
        'due_date',
        'total',
        'status',
        'public_identifier',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->due_date) && !empty($invoice->period)) {
                $days = (int) env('INVOICE_DUE_DAYS', 10);
                $invoice->due_date = \Carbon\Carbon::parse($invoice->period)->addDays($days);
            }
            // Generar identificador único público
            if (empty($invoice->public_identifier)) {
                do {
                    $identifier = strtoupper(bin2hex(random_bytes(5)));
                } while (self::where('public_identifier', $identifier)->exists());
                $invoice->public_identifier = $identifier;
            }
        });
    }

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
