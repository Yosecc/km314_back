<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Support\Str;

trait HasStrictWidgetShield
{
    public static function canView(): bool
    {
        return Filament::auth()->user()?->can(static::getPermissionName()) ?? false;
    }

    protected static function getPermissionName(): string
    {
        return Str::of(class_basename(static::class))
            ->prepend(config('filament-shield.permission_prefixes.widget', 'widget').'_')
            ->toString();
    }
}
