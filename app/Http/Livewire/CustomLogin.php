<?php

namespace App\Http\Livewire;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class CustomLogin extends BaseLogin
{
    public $terms = false;

    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        if (! $this->terms) {
            throw ValidationException::withMessages([
                'terms' => 'Debes aceptar los términos y condiciones para continuar.',
            ]);
        }
        return parent::authenticate();
    }
}
