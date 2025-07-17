<?php

namespace App\Filament\Resources\FormIncidentCategoryQuestionResource\Pages;

use App\Filament\Resources\FormIncidentCategoryQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormIncidentCategoryQuestion extends EditRecord
{
    protected static string $resource = FormIncidentCategoryQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
