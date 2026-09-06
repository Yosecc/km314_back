<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProveedorEmpleado extends Model
{
    use HasFactory;

    protected $table = 'proveedores_empleados';

    protected $fillable = [
        'proveedor_id',
        'nombre',
        'apellido',
        'dni',
        'archivo_dni',
        'telefono',
    ];

    public function setArchivoDniAttribute($value): void
    {
        if (is_array($value)) {
            $value = collect($value)->filter()->first();
        }

        $this->attributes['archivo_dni'] = $value;
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
}
