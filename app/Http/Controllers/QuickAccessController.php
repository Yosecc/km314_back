<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FormControl;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class QuickAccessController extends Controller
{
    /**
     * Handle quick access by code (encrypted or plain)
     */
    public function index($code)
    {
        // Intentar desencriptar si está encriptado
        try {
            $decryptedCode = Crypt::decryptString($code);
        } catch (\Exception $e) {
            // Si no se puede desencriptar, asumir que es código plano
            $decryptedCode = $code;
        }
        
        // Buscar en todas las entidades
        $entity = $this->findByCode($decryptedCode);
        
        if (!$entity) {
            abort(404, 'Código de acceso rápido no encontrado');
        }

        // Determinar tipo
        $type = $this->getEntityType($entity);
        $entityType = $this->getEntityTypeName($entity);
        
        // Mostrar página pública con información
        return view('quick-access', [
            'entity' => $entity,
            'code' => $decryptedCode,
            'type' => $type,
            'entityType' => $entityType,
            'loginUrl' => route('filament.admin.auth.login')
        ]);
    }

    /**
     * Find entity by quick access code
     */
    private function findByCode($code)
    {
        // Buscar en Employee
        $employee = Employee::where('quick_access_code', $code)->first();
        if ($employee) {
            return $employee;
        }

        // Buscar en Owner
        $owner = Owner::where('quick_access_code', $code)->first();
        if ($owner) {
            return $owner;
        }

        // Buscar en FormControl
        $formControl = FormControl::where('quick_access_code', $code)->first();
        if ($formControl) {
            return $formControl;
        }

        return null;
    }

    /**
     * Get entity type identifier
     */
    private function getEntityType($entity): int
    {
        if ($entity instanceof Employee) {
            return 2; // tipo_entrada para empleados
        } elseif ($entity instanceof Owner) {
            return 1; // tipo_entrada para propietarios
        } elseif ($entity instanceof FormControl) {
            return 3; // tipo_entrada para formularios de control
        }

        return 0;
    }

    /**
     * Get entity type name
     */
    private function getEntityTypeName($entity): string
    {
        if ($entity instanceof Employee) {
            return 'Empleado';
        } elseif ($entity instanceof Owner) {
            return 'Propietario';
        } elseif ($entity instanceof FormControl) {
            return 'Formulario de Control';
        }

        return 'Desconocido';
    }
}
