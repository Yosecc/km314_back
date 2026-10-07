<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use App\Filament\Resources\AccountStatusResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountStatus extends ViewRecord
{
    protected static string $resource = AccountStatusResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
