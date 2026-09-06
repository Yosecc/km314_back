<?php

namespace App\Filament\Resources\FormControlResource\Pages;

use Filament\Actions;
use App\Models\FormControl;
use App\Traits\HasQrCodeAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\FormControlResource;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Models\User;

class ViewFormControl extends ViewRecord
{
    use HasQrCodeAction;
    
    protected static string $resource = FormControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ownerApprove')
                ->label('Aprobar solicitud')
                ->icon('heroicon-m-hand-thumb-up')->color('success')->requiresConfirmation()
                ->modalDescription('Al aprobarlo, el formulario será enviado a administración para su revisión final.')
                ->visible(fn (FormControl $record) => auth()->user()->hasRole('owner') && $record->status === 'OwnerPending' && (int) $record->owner_id === (int) auth()->user()->owner_id)
                ->action(function (FormControl $record): void {
                    $record->approveByOwner(auth()->user());
                    $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name',['super_admin','admin','Administrador']))->get();
                    Notification::make()->title('Formulario pendiente de aprobación administrativa')->body('El propietario aprobó el formulario #'.$record->id.'.')->sendToDatabase($admins);
                    Notification::make()->title('Formulario enviado a administración')->success()->send();
                }),
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
