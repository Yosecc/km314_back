<?php

namespace App\Filament\Resources\EmployeeResource\Pages\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait HasFriendlyEmployeeValidation
{
    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title('Revise los datos del trabajador')
            ->body('Hay campos incompletos o inválidos. Corrija los campos marcados en rojo e intente nuevamente.')
            ->danger()
            ->send();
    }
}
