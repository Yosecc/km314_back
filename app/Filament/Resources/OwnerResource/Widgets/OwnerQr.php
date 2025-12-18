<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Owner;
use Livewire\Component;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

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
    
    public function openModal()
    {
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        
        $this->showModal = false;
    }
}
