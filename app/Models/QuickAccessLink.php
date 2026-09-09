<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickAccessLink extends Model
{
    protected $fillable = ['user_id', 'name', 'url', 'icon', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
