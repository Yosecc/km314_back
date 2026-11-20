<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class InterestedCalendar extends Page
{
    use HasPageShield {
        canAccess as protected canAccessPage;
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.interested-calendar';

    protected static ?string $navigationLabel = 'Calendario comercial';

    protected static ?string $title = 'Calendario comercial';

    protected static ?string $slug = 'interested-calendar';

    protected static ?string $navigationGroup = 'Interesados';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return static::canAccessPage() && (auth()->user()?->can('view_any_interested::follow::up') ?? false);
    }
}
