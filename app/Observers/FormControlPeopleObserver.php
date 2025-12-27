<?php

namespace App\Observers;

use App\Models\FormControlPeople;

class FormControlPeopleObserver
{
    /**
     * Handle the FormControlPeople "creating" event.
     */
    public function creating(FormControlPeople $formControlPeople): void
    {
        // Asegurar que los campos booleanos nunca sean null
        $formControlPeople->is_responsable = $formControlPeople->is_responsable ?? false;
        $formControlPeople->is_acompanante = $formControlPeople->is_acompanante ?? false;
        $formControlPeople->is_menor = $formControlPeople->is_menor ?? false;
    }

    /**
     * Handle the FormControlPeople "updating" event.
     */
    public function updating(FormControlPeople $formControlPeople): void
    {
        // También al actualizar
        $formControlPeople->is_responsable = $formControlPeople->is_responsable ?? false;
        $formControlPeople->is_acompanante = $formControlPeople->is_acompanante ?? false;
        $formControlPeople->is_menor = $formControlPeople->is_menor ?? false;
    }
}
