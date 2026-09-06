<?php

namespace App\Filament\Resources\OwnerResource\Widgets;

use App\Filament\Concerns\HasStrictWidgetShield as HasWidgetShield;
use App\Models\Owner;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OwnerQr extends Widget
{
    use HasWidgetShield {
        canView as protected shieldCanView;
    }

    public ?Owner $record = null;

    protected static string $view = 'filament.widgets.owner-qr';

    protected static ?string $heading = 'Código QR del Propietario';

    public bool $showModal = false;

    protected int|string|array $columnSpan = 'full';

    public function mount(?Owner $record = null): void
    {
        $this->record = auth()->user()->hasRole('owner') ? auth()->user()->owner : $record;
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user
            && static::shieldCanView()
            && (! $user->hasRole('owner') || $user->is_terms_condition);
    }
}
