<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterestedFollowUp extends Model
{
    use HasFactory, SoftDeletes;

    public const PENDING = 'pending';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'interested_id', 'assigned_user_id', 'created_by_user_id', 'completed_by_user_id',
        'title', 'channel', 'notes', 'due_at', 'reminder_at', 'status', 'completed_at', 'notified_at',
    ];

    protected $casts = [
        'due_at' => 'datetime', 'reminder_at' => 'datetime', 'completed_at' => 'datetime', 'notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        $sync = function (InterestedFollowUp $followUp): void {
            if ($interested = Interested::withTrashed()->find($followUp->interested_id)) {
                app(\App\Services\InterestedCrmService::class)->syncNextContact($interested);
            }
        };

        static::saved($sync);
        static::deleted($sync);
        static::restored($sync);
    }

    public function interested()
    {
        return $this->belongsTo(Interested::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::PENDING && $this->due_at->isPast();
    }
}
