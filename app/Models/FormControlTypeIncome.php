<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlTypeIncome extends Model
{
    use HasFactory;

    protected $fillable = [ 'name', 'color', 'status', 'terminos', 'files_required' ];

    protected $casts = [
        'files_required' => 'array',
    ];

    public function subtipos()
    {
        return $this->hasMany(Trabajos::class);
    }
}
