<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use Filament\Actions;
use App\Models\FormControl;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\FormControlResource;
use App\Traits\HasQrCodeAction;


class EditFormControl extends EditRecord
{
    use HasQrCodeAction;
    
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getQrCodeAction(),
            Actions\DeleteAction::make(),
     
                    Actions\Action::make('aprobar')
                        ->requiresConfirmation()
                        ->color('success')
                        ->label('Aprobar')
                        ->action(function(FormControl $record){

                            $record->aprobar();
                            Notification::make()
                                ->title('Formulario aprobado')
                                ->success()
                                ->send();


                        })
                        ->hidden(function(FormControl $record){
                            return $record->isActive() || $record->isExpirado() || $record->isVencido() ? true : false;
                        })
                        ->visible(auth()->user()->can('aprobar_form::control')),

                    Actions\Action::make('rechazar')
                        ->action(function(FormControl $record){
                            $record->rechazar();
                            Notification::make()
                                ->title('Formulario rechazado')
                                ->success()
                                ->send();

                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-m-hand-thumb-down')
                        ->color('danger')
                        ->label('Rechazar')
                        ->visible(auth()->user()->can('rechazar_form::control'))
                        ->hidden(function(FormControl $record){
                            return $record->isDenied() || $record->isExpirado() || $record->isVencido() ? true : false;
                        })
                
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FormControlResource\Widgets\StatusOverview::make(['record' => $this->record]),
        ];
    }

    protected function getWidgetsData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}
