<?php

namespace App\Filament\Widgets;


use Livewire\Component;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\Auth;


class UserTermsConditionsCheck extends Widget
{
    use HasWidgetShield;

    protected static string $view = 'filament.widgets.user-terms-conditions-check';
    
    protected static string $heading = 'Confirmación de Términos y Condiciones';
    
    protected int | string | array $columnSpan = 'full';


    public $accepted = false;

    public function acceptTerms()
    {
        $user = auth()->user();
        if ($user && !$user->is_terms_condition) {
            $user->is_terms_condition = true;
            $user->save();

            Notification::make()
                ->title('Terminos y Condiciones aceptados')
                ->success()
                ->send();

            $this->dispatch('terms-accepted');
        }
    }

    public static function canView(): bool
    {
        // Si el usuario no ha aceptado los términos, no puede ver el recurso
        
            $user = auth()->user();
            return $user && $user->is_terms_condition && auth()->user()->can('widget_UserTermsConditionsCheck');
        

   
    }

}
