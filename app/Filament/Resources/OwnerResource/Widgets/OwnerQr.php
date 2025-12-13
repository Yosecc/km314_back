<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Owner;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Traits\HasQrCodeAction;

class OwnerQr extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use HasQrCodeAction;
    
    protected static string $view = 'filament.widgets.owner-qr';
    
    public ?Owner $record = null;
    
    protected int | string | array $columnSpan = 'full';
    
    public function mount(?Owner $record = null): void
    {
        $this->record = auth()->user()->hasRole('owner') ? auth()->user()->owner : $record;
    }
    
    public function showQrAction()
    {
        return $this->getQrCodeAction();
    }
}
