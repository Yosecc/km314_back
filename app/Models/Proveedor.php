<?php

namespace App\Models;

use App\Traits\HasQuickAccessCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory, HasQuickAccessCode;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'quick_access_code',
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

    public function formControls()
    {
        return $this->hasMany(FormControl::class);
    }

    public function activities()
    {
        return $this->hasMany(Activities::class);
    }
}
