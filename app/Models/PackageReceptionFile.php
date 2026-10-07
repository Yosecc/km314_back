<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageReceptionFile extends Model
{
    public const REGISTRATION = 'registration';
    public const RECEPTION = 'reception';
    public const DELIVERY_IDENTITY = 'delivery_identity';

    protected $fillable = ['category', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by_user_id'];
    public function packageReception() { return $this->belongsTo(PackageReception::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by_user_id'); }
}
