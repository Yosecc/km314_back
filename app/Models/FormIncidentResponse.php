<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormIncidentResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'form_incident_type_id',
        'user_id',
        'date',
        'time',
        'answers',
        'read_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'read_at' => 'datetime',
    ];
    public function type()
    {
        return $this->belongsTo(FormIncidentType::class, 'form_incident_type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar el formulario como leído
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Verificar si el formulario ha sido leído
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Scope para formularios no leídos
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope para formularios leídos
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }
}
