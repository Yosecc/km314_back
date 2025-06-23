<?php

namespace App\Filament\Resources\FormIncidentQuestionResource\Pages;

use App\Filament\Resources\FormIncidentQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormIncidentQuestion extends EditRecord
{
    protected static string $resource = FormIncidentQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
