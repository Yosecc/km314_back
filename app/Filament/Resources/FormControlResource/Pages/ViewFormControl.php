<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use Filament\Actions;
use App\Models\FormControl;
use App\Traits\HasQrCodeAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\FormControlResource;

class ViewFormControl extends ViewRecord
{
    use HasQrCodeAction;
    
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getQrCodeAction(),
            Actions\EditAction::make()
            ->hidden(function($record){
                return !auth()->user()->hasRole(['super_admin', 'admin']);
            }),
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


                        if($record->owner && $record->owner->user){
                            Notification::make()
                            ->title('Formulario aprobado')
                            ->body('Ahora las personas confioguradas en el formulario podrán acceder al barrio según los horarios establecidos')
                            ->actions([
                                NotificationAction::make('Ver Formulario')
                                    ->button()
                                    ->url(route('filament.admin.resources.form-controls.view', $record), shouldOpenInNewTab: true)
                            ])
                            ->sendToDatabase($record->owner->user);
                        }
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

                        if($record->owner && $record->owner->user){
                            Notification::make()
                            ->title('Formulario rechazado')
                            ->sendToDatabase($record->owner->user);
                        }
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
}
