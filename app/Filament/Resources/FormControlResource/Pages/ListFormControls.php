<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use App\Filament\Resources\FormControlResource;
use App\Filament\Resources\FormControlResource\Pages\Concerns\HasPublicFormLinkAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFormControls extends ListRecords
{
    use HasPublicFormLinkAction;

    protected static string $resource = FormControlResource::class;
    protected static string $view = 'filament.resources.form-control-resource.pages.list-form-controls';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('formControlTutorial')
                ->label('Cómo funciona')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->action(fn () => $this->dispatch('open-form-control-tutorial')),
            $this->sharePublicFormAction(),
            Actions\CreateAction::make(),
        ];
    }
}
