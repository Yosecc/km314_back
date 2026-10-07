<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PackageReception extends Model
{
    use HasFactory, SoftDeletes;

    public const EXPECTED = 'expected';
    public const RECEIVED = 'received';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_code', 'owner_id', 'lote_id', 'created_by_user_id', 'courier_name',
        'expected_from', 'expected_until', 'carrier_access_code', 'tracking_number',
        'recipient_name', 'recipient_dni', 'recipient_phone', 'expected_packages_count',
        'received_packages_count', 'observations', 'status', 'received_at',
        'received_by_user_id', 'reception_notes', 'cancelled_at', 'cancelled_by_user_id',
        'cancellation_reason', 'delivered_at', 'delivered_by_user_id', 'delivered_to_type',
        'delivered_to_name', 'delivered_to_dni', 'delivery_notes', 'overdue_notified_at',
        'pickup_reminder_sent_at', 'pickup_critical_sent_at',
    ];

    protected $casts = [
        'expected_from' => 'datetime', 'expected_until' => 'datetime', 'received_at' => 'datetime',
        'cancelled_at' => 'datetime', 'delivered_at' => 'datetime', 'overdue_notified_at' => 'datetime',
        'pickup_reminder_sent_at' => 'datetime', 'pickup_critical_sent_at' => 'datetime',
        'carrier_access_code' => 'encrypted', 'expected_packages_count' => 'integer',
        'received_packages_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->reference_code ??= self::nextReference();
            $record->status ??= self::EXPECTED;
        });
    }

    public static function nextReference(): string
    {
        do {
            $code = 'PAQ-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (self::withTrashed()->where('reference_code', $code)->exists());
        return $code;
    }

    public static function statuses(): array
    {
        return [self::EXPECTED => 'En espera', self::RECEIVED => 'Recibido', self::DELIVERED => 'Entregado', self::CANCELLED => 'Cancelado'];
    }

    public function owner() { return $this->belongsTo(Owner::class); }
    public function lote() { return $this->belongsTo(Lote::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function receivedBy() { return $this->belongsTo(User::class, 'received_by_user_id'); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by_user_id'); }
    public function deliveredBy() { return $this->belongsTo(User::class, 'delivered_by_user_id'); }
    public function files() { return $this->hasMany(PackageReceptionFile::class); }
    public function registrationFiles() { return $this->files()->where('category', PackageReceptionFile::REGISTRATION); }
    public function events() { return $this->hasMany(PackageReceptionEvent::class)->orderByDesc('occurred_at'); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole('owner') ? $query->where('owner_id', $user->owner_id) : $query;
    }

    public function isExpectedOverdue(): bool { return $this->status === self::EXPECTED && $this->expected_until->isPast(); }
    public function pickupAlertLevel(): ?string
    {
        if ($this->status !== self::RECEIVED || ! $this->received_at) return null;
        $hours = $this->received_at->diffInHours(now());
        if ($hours >= config('package-receptions.pickup_critical_hours')) return 'critical';
        if ($hours >= config('package-receptions.pickup_warning_hours')) return 'warning';
        return null;
    }
}
