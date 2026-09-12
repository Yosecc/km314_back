<?php

namespace App\Filament\Resources\RecurrentVisitorResource\Pages;

use App\Filament\Resources\RecurrentVisitorResource;
use App\Services\ApplicationNotificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageRecurrentVisitors extends ManageRecords
{
    protected static string $resource = RecurrentVisitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function ($record) {
                    if (!auth()->user()->hasRole('owner')) {
                        return;
                    }

                    Notification::make()
                        ->title('Visitante recurrente registrado')
                        ->body('El visitante quedó pendiente de aprobación.')
                        ->success()
                        ->send();

                    app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                        ['aprobar_recurrent::visitor', 'rechazar_recurrent::visitor'],
                        'Nuevo visitante recurrente pendiente de aprobación',
                        auth()->user()->name . ' registró a ' . $record->nombres() . '.',
                        ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $record->id, 'status' => 'pendiente'],
                        RecurrentVisitorResource::getUrl('index'),
                        'heroicon-o-user-plus',
                    );
                }),
        ];
    }
}
