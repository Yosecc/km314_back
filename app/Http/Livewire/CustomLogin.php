<?php

namespace App\Http\Livewire;

use Filament\Http\Livewire\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class CustomLogin extends BaseLogin
{
    public $terms = false;

    public function authenticate()
    {
        if (! $this->terms) {
            throw ValidationException::withMessages([
                'terms' => 'Debes aceptar los términos y condiciones para continuar.',
            ]);
        }
        parent::authenticate();
    }
}
