<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormControlPublicInvitation extends Model
{
    protected $fillable = ['token_hash','owner_id','lote_id','created_by_user_id','expires_at','used_at','used_form_control_id','revoked_at'];
    protected $casts = ['expires_at'=>'datetime','used_at'=>'datetime','revoked_at'=>'datetime'];

    public static function issue(Owner $owner, Lote $lote, User $creator): array
    {
        $plain = Str::random(64);
        $record = self::create([
            'token_hash'=>hash('sha256',$plain), 'owner_id'=>$owner->id, 'lote_id'=>$lote->id,
            'created_by_user_id'=>$creator->id, 'expires_at'=>now()->addDays(7),
        ]);
        return [$record, $plain];
    }

    public static function resolveToken(string $token): ?self
    {
        return self::query()->with(['owner','lote.sector','creator'])->where('token_hash',hash('sha256',$token))->first();
    }

    public function isAvailable(): bool { return ! $this->used_at && ! $this->revoked_at && $this->expires_at->isFuture(); }
    public function owner() { return $this->belongsTo(Owner::class); }
    public function lote() { return $this->belongsTo(Lote::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function usedFormControl() { return $this->belongsTo(FormControl::class, 'used_form_control_id'); }
}
