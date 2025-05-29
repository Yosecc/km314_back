<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use App\Filament\Resources\AccountStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\AccountStatusResource\Widgets\MovementsTableWidget;

class ViewAccountStatus extends ViewRecord
{
    protected static string $resource = AccountStatusResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MovementsTableWidget::make(['record' => $this->record]),
        ];
    }
}
