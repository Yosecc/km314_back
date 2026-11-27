<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlTypeIncome extends Model
{
    use HasFactory;

    public const TEMPORARY_VISIT = 'Visita Temporal (12hs)';
    public const LEGACY_TEMPORARY_VISIT = 'Visita Temporal (24hs)';
    public const TEMPORARY_VISIT_HOURS = 12;

    protected $fillable = [ 'name', 'color', 'status', 'terminos', 'files_required' ];

    protected $casts = [
        'files_required' => 'array',
    ];

    public function subtipos()
    {
        return $this->hasMany(Trabajos::class);
    }
}
