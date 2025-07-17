<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormIncidentUserRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'form_incident_type_id',
        'frequency',
        'deadline_time',
        'days_of_week',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'deadline_time' => 'datetime:H:i',
        'days_of_week' => 'array',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formIncidentType(): BelongsTo
    {
        return $this->belongsTo(FormIncidentType::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDaily($query)
    {
        return $query->where('frequency', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('frequency', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('frequency', 'monthly');
    }
}
