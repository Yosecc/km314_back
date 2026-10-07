<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageReceptionEvent extends Model
{
    protected $fillable = ['event_type', 'from_status', 'to_status', 'actor_user_id', 'notes', 'metadata', 'occurred_at'];
    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];
    public function packageReception() { return $this->belongsTo(PackageReception::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_user_id'); }
}
