<?php

namespace App\Filament\Resources\FormControlTypeIncomeResource\Pages;

use App\Filament\Resources\FormControlTypeIncomeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFormControlTypeIncomes extends ManageRecords
{
    protected static string $resource = FormControlTypeIncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
