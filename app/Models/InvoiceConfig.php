<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period',
        'fecha_creacion',
        'config',
        'expiration_date',
        'second_expiration_date',
        'punitive',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'period' => 'date',
        'expiration_date' => 'date',
        'second_expiration_date' => 'date',
        'fecha_creacion' => 'date',
        'punitive' => 'integer',
        'status' => 'string', // Enum, pero casteado como string para Eloquent
    ];

    // Opcional: método para obtener el label del status
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'Borrador' => 'Borrador',
            'Aprobado' => 'Aprobado',
            'Procesado' => 'Procesado',
            default => ucfirst($this->status),
        };
    }
}
