<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageEmployees extends ManageRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // Siempre establecer fecha_vencimiento_seguro
                    $data['fecha_vencimiento_seguro'] = Carbon::now()->addMonths(3)->toDateString();
                    return $data;
                })
                ->after(function ($record) {
                    
                    if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                        $record->owners()->attach(Auth::user()->owner_id);
                    }
                    
                    // Enviar notificación si es un owner quien crea el registro
                    if (Auth::user()->hasRole('owner')) {
                        $this->sendEmployeeCreatedNotification($record);
                    }
                }),
        ];
    }

    protected function sendEmployeeCreatedNotification($employee)
    {
        // Obtener todos los usuarios admin
        $admins = User::role('super_admin')->get(); // Asumiendo que usas Spatie Permission

        foreach ($admins as $admin) {
            Notification::make()
                ->title('Nuevo trabajador pendiente de aprobación')
                ->body("El propietario " . Auth::user()->name . " ha registrado un nuevo trabajador: " . $employee->first_name . " " . $employee->last_name)
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('Ver trabajador')
                        ->url(EmployeeResource::getUrl('view', ['record' => $employee->id]))
                        ->button(),
                ])
                ->sendToDatabase($admin);
        }

        // Notificación para el owner confirmando el registro
        Notification::make()
            ->title('Trabajador registrado')
            ->body('El trabajador ha sido registrado exitosamente y está pendiente de aprobación.')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->send();
    }
}
