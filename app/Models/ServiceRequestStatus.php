<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestStatus extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['name', 'code', 'color'];

    public static function options(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public static function defaultPending(): self
    {
        return static::query()->where('code', self::PENDING)->first()
            ?? static::query()->orderBy('id')->firstOrFail();
    }

}
