<?php

namespace App\Filament\Resources\ExpenseStatusResource\Pages;

use App\Filament\Resources\ExpenseStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageExpenseStatuses extends ManageRecords
{
    protected static string $resource = ExpenseStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
