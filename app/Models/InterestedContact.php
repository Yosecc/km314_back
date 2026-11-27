<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterestedContact extends Model
{
    use HasFactory, SoftDeletes;

    public const CHANNELS = [
        'phone' => 'Teléfono', 'whatsapp' => 'WhatsApp', 'email' => 'Email',
        'facebook' => 'Facebook', 'instagram' => 'Instagram', 'in_person' => 'Presencial', 'other' => 'Otro',
    ];

    public const DIRECTIONS = ['outgoing' => 'Saliente', 'incoming' => 'Entrante'];

    protected $fillable = ['interested_id', 'user_id', 'channel', 'direction', 'result', 'notes', 'contacted_at'];

    protected $casts = ['contacted_at' => 'datetime'];

    protected static function booted(): void
    {
        $sync = function (InterestedContact $contact): void {
            if ($interested = Interested::withTrashed()->find($contact->interested_id)) {
                $interested->forceFill([
                    'last_contacted_at' => $interested->contacts()->max('contacted_at'),
                ])->saveQuietly();
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
