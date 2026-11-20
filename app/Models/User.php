<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'owner_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'package_reception_tutorial_seen_at' => 'datetime',
        'service_request_tutorial_seen_at' => 'datetime',
    ];

    protected $with = ['owner'];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function formIncidentRequirements()
    {
        return $this->hasMany(\App\Models\FormIncidentUserRequirement::class);
    }

    public function quickAccessLinks()
    {
        return $this->hasMany(QuickAccessLink::class)->orderBy('sort_order')->orderBy('id');
    }

    public function fcmDevices(): HasMany
    {
        return $this->hasMany(FcmDevice::class);
    }

    public function assignedInteresteds(): HasMany
    {
        return $this->hasMany(Interested::class, 'assigned_user_id');
    }

    public function interestedFollowUps(): HasMany
    {
        return $this->hasMany(InterestedFollowUp::class, 'assigned_user_id');
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class);
    // }
}
