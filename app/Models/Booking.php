<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = ['booking_status_id','lote_id','interested_id','propertie_id','interested_type_operation_id','operation_detail','amount','date_end'];

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
