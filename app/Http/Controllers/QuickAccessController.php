<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FormControl;
use App\Models\Owner;
use Illuminate\Http\Request;

class QuickAccessController extends Controller
{
    /**
     * Handle quick access by code
     */
    public function index($code)
    {
        // Buscar en todas las entidades
        $entity = $this->findByCode($code);
        
        if (!$entity) {
            abort(404, 'Código de acceso rápido no encontrado');
        }

        // Determinar tipo y redirigir a crear actividad con parámetros
        $type = $this->getEntityType($entity);
        
        return redirect()->route('filament.admin.resources.activities.create', [
            'quick_code' => $code,
            'entity_type' => $type,
            'entity_id' => $entity->id
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
}
