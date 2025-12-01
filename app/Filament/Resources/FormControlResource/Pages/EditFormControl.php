<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use Filament\Actions;
use Filament\Actions\Action as PageAction;
use App\Models\FormControl;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\FormControlResource;
use Filament\Forms\Components\Actions\Action as FormAction;


class EditFormControl extends EditRecord
{
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
     
                    FormAction::make('aprobar')
                        ->button()
                        ->requiresConfirmation()
                        ->color('success')
                        ->label('Aprobar')
                        ->action(function(FormControl $record){

                            $record->aprobar();
                            Notification::make()
                                ->title('Formulario aprobado')
                                ->success()
                                ->send();


                                if($record->owner && $record->owner->user){
                                    Notification::make()
                                    ->title('Formulario aprobado')
                                    ->sendToDatabase($record->owner->user);
                                }
                        })
                        ->hidden(function(FormControl $record){
                            return $record->isActive() || $record->isExpirado() || $record->isVencido() ? true : false;
                        })
                        ->visible(auth()->user()->can('aprobar_form::control')),

                    FormAction::make('rechazar')
                        ->action(function(FormControl $record){
                            $record->rechazar();
                            Notification::make()
                                ->title('Formulario rechazado')
                                ->success()
                                ->send();

                                if($record->owner && $record->owner->user){
                                    Notification::make()
                                    ->title('Formulario rechazado')
                                    ->sendToDatabase($record->owner->user);
                                }
                        })
                        ->button()
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
}
