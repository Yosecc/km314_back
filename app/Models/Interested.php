<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interested extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name', 'observations', 'dni', 'last_name', 'email', 'phone', 'address', 'city', 'state',
        'zip_code', 'country', 'interested_origins_id', 'interested_type_id', 'interested_level_id',
        'interested_status_id', 'assigned_user_id', 'last_contacted_at', 'next_contact_at', 'lote_id', 'propertie_id',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'next_contact_at' => 'datetime',
    ];

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

    public function interestedType()
    {
        return $this->belongsTo(InterestedType::class);
    }

    public function interestedLevel()
    {
        return $this->belongsTo(InterestedLevel::class);
    }

    public function interestedStatus()
    {
        return $this->belongsTo(InterestedStatus::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contacts()
    {
        return $this->hasMany(InterestedContact::class)->latest('contacted_at');
    }

    public function latestContact()
    {
        return $this->hasOne(InterestedContact::class)->latestOfMany('contacted_at');
    }

    public function followUps()
    {
        return $this->hasMany(InterestedFollowUp::class)->orderBy('due_at');
    }

    public function pendingFollowUps()
    {
        return $this->hasMany(InterestedFollowUp::class)->pending()->orderBy('due_at');
    }

    public function nextFollowUp()
    {
        return $this->hasOne(InterestedFollowUp::class)
            ->ofMany(['due_at' => 'min'], fn ($query) => $query->where('status', InterestedFollowUp::PENDING));
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->phone);

        return $number ? 'https://wa.me/'.$number : null;
    }

    public function propertie()
    {
        return $this->belongsTo(Property::class);
    }
}
