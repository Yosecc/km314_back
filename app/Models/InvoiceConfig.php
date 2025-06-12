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
        'expiration_date',
        'second_expiration_date',
        'punitive'
    ];

    protected $casts = [
        'config' => 'array',
        'period' => 'date',
        'expiration_date' => 'date',
        'second_expiration_date' => 'date',
        'fecha_creacion' => 'date',
        'punitive' => 'integer'
    ];
}
