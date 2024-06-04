<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Interested extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['first_name','observations','dni','last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code', 'country','interested_origins_id','lote_id','propertie_id'];
    
    public function booking()
    {
        return $this->hasMany(Booking::class);
    } 

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function interestedOrigins()
    {
        return $this->belongsTo(InterestedOrigin::class);
    }

    public function propertie()
    {
        return $this->belongsTo(Property::class);
    }

    
}
