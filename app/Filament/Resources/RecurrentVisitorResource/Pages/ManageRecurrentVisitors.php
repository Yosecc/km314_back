<?php

namespace App\Filament\Resources\RecurrentVisitorResource\Pages;

use App\Filament\Resources\RecurrentVisitorResource;
use App\Models\User;
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

                    Notification::make()
                        ->title('Nuevo visitante recurrente pendiente de aprobación')
                        ->body(auth()->user()->name . ' registró a ' . $record->nombres())
                        ->sendToDatabase(User::role('super_admin')->get());
                }),
        ];
    }
}
