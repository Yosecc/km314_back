<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditIncident extends EditRecord
{
    protected static string $resource = IncidentResource::class;

    protected function afterSave(): void
    {
        $idUsers = $this->record->notes->pluck('user_id')
                        ->push($this->record->user_id)
                        ->unique()
                        ->reject(function ($value) {
                            return $value === Auth::user()->id;
                        })
                        ->values()
                        ->all()
                        ;

        // dd($idUsers);
        try {
            $recipient = User::whereIn('id', $idUsers)->get();

            if(!$recipient){
                return;
            }

            Notification::make()
                ->title('Inicidencia actualziada: '. $this->record->name)
                ->sendToDatabase($recipient);

        } catch (\Throwable $th) {
        //throw $th;
        }
    }
}
