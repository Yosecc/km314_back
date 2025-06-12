<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'fecha_creacion',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'period' => 'date',
        'fecha_creacion' => 'date',
    ];
}
