<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Owner;

class OwnerQr extends Widget
{
    protected static string $view = 'filament.widgets.owner-qr';
    
    public Owner $record;
    
    protected int | string | array $columnSpan = 'full';
}
