<?php

namespace App\Filament\Resources\FormIncidentQuestionResource\Pages;

use App\Filament\Resources\FormIncidentQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormIncidentQuestions extends ListRecords
{
    protected static string $resource = FormIncidentQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
