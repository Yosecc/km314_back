<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Owner;
use Livewire\Component;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\Auth;

class OwnerQr extends Widget
{
    use HasWidgetShield;

    public ?Owner $record = null;

    protected static string $view = 'filament.widgets.owner-qr';
    
    protected static ?string $heading = 'Código QR del Propietario';

    public bool $showModal = false;
    
    protected int | string | array $columnSpan = 'full';
    
    public function mount(?Owner $record = null): void
    {
        $this->record = auth()->user()->hasRole('owner') ? auth()->user()->owner : $record;
    }

    public static function canView(): bool
    {
       // Si el usuario no ha aceptado los términos, no puede ver el recurso
        if(Auth::user()->hasRole('owner')){
            $user = auth()->user();
            return $user && $user->is_terms_condition;
        }

        return auth()->user()->can('widget_OwnerQr');
    }
    
}
