<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestedStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'sort_order', 'is_closed'];

    protected $casts = ['is_closed' => 'boolean'];

    public function interesteds()
    {
        return $this->hasMany(Interested::class);
    }
}
