<?php

namespace App\Filament\Widgets;


use Filament\Widgets\Widget;
use Livewire\Component;


class UserTermsConditionsCheck extends Widget
{
    protected static string $view = 'filament.widgets.user-terms-conditions-check';

    public $accepted = false;

    public function acceptTerms()
    {
        $user = auth()->user();
        if ($user && !$user->is_terms_condition) {
            $user->is_terms_condition = true;
            $user->save();
            $this->dispatch('terms-accepted');
        }
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && !$user->is_terms_condition;
    }
}
