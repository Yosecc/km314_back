<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['booking_status_id', 'lote_id', 'interested_id', 'propertie_id', 'interested_type_operation_id', 'operation_detail', 'amount', 'date_end'];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_end' => 'date',
    ];

    public function interested()
    {
        return $this->belongsTo(Interested::class);
    }

    public function bookingStatus()
    {
        return $this->belongsTo(BookingStatus::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function interested_type_operation()
    {
        return $this->belongsTo(InterestedTypeOperation::class);
    }

    public function propertie()
    {
        return $this->belongsTo(Property::class);
    }
}
