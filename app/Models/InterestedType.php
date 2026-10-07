<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterestedType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function interesteds()
    {
        return $this->hasMany(Interested::class);
    }
}
