<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'telefono_empresa',
        'cuit_empresa',
        'nombre_responsable',
        'telefono_responsable',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Proveedor $proveedor): void {
            Auto::withTrashed()
                ->where('model', 'Proveedor')
                ->where('model_id', $proveedor->getKey())
                ->get()
                ->each
                ->forceDelete();

            $proveedor->empleados()->delete();
        });
    }

    public function autos()
    {
        return $this->hasMany(Auto::class, 'model_id')->where('model', 'Proveedor');
    }

    public function empleados()
    {
        return $this->hasMany(ProveedorEmpleado::class);
    }
}
