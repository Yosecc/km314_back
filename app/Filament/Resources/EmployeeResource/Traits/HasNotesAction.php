<?php

namespace App\Filament\Resources\EmployeeResource\Traits;

use App\Models\Employee;
use App\Models\EmployeeNote;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\Action as PageAction;
use Illuminate\Support\Facades\Auth;

trait HasNotesAction
{
    /**
     * Action para usar en páginas (EditEmployee, etc)
     */
    public static function getNotesPageAction(): PageAction
    {
        return PageAction::make('notas')
            ->label('Notificaciones')
            ->icon('heroicon-o-bell')
            ->color('info')
            ->badge(fn (Employee $record) => Auth::user()->hasRole('owner') ? $record->notes()->where('status', false)->count() ?: null : null)
            ->badgeColor('danger')
            ->modalHeading(fn ($record) => 'Notificaciones - ' . $record->first_name . ' ' . $record->last_name)
            ->modalWidth('3xl')
            ->modalContent(fn (Employee $record) => view('filament.components.employee-notes', [
                'notes' => $record->notes()->with('user')->orderBy('created_at', 'desc')->get(),
                'employee' => $record
            ]))
            ->mountUsing(function (Employee $record) {
                // Marcar todas las notas como leídas al abrir el modal
                if(Auth::user()->hasRole('owner')){
                    $record->notes()->where('status', false)->update(['status' => true]);
                }
            })
            ->visible(function(Employee $record){

                if(Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin')){
                    return true;
                }

                return Auth::user()->hasRole('owner') && $record->notes()->where('status', false)->count() ? true : false;
            })
            ->form([
                Forms\Components\Textarea::make('description')
                    ->label('Nueva notificación')
                    ->placeholder('Escribe una nueva notificación...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn () => Auth::user()->hasRole(['super_admin','admin']))
                    ,
            ])
            ->action(function (array $data, Employee $record): void {
                EmployeeNote::create([
                    'description' => $data['description'],
                    'employee_id' => $record->id,
                    'user_id' => Auth::id(),
                    'status' => false, // No leída al crear
                ]);

                Notification::make()
                    ->title('Nueva notificación. Ir a Gestión de trabajadores')
                    ->sendToDatabase($record->owner->user);

                Notification::make()
                    ->title('Notificación agregada')
                    ->success()
                    ->send();
            })
            ->modalSubmitActionLabel('Agregar notificación')
            ->modalSubmitAction(fn ($action) => $action->visible(fn () => Auth::user()->hasRole(['super_admin','admin'])))
            ;
    }

    /**
     * Action para usar en tablas
     */
    public static function getNotesTableAction(): TableAction
    {
        return TableAction::make('notas')
            ->label('Notificaciones')
            ->icon('heroicon-o-bell')
            ->color('info')
            ->badge(fn (Employee $record) => Auth::user()->hasRole('owner') ? $record->notes()->where('status', false)->count() ?: null : null)
            ->badgeColor('danger')
            ->modalHeading(fn (Employee $record) => 'Notificaciones - ' . $record->first_name . ' ' . $record->last_name)
            ->modalWidth('3xl')
            ->modalContent(fn (Employee $record) => view('filament.components.employee-notes', [
                'notes' => $record->notes()->with('user')->orderBy('created_at', 'desc')->get(),
                'employee' => $record
            ]))
            ->mountUsing(function (Employee $record) {
                // Marcar todas las notas como leídas al abrir el modal
                if(Auth::user()->hasRole('owner')){
                    $record->notes()->where('status', false)->update(['status' => true]);
                }
            })->visible(function(Employee $record){
                if(Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin')){
                    return true;
                }
                return $record->notes()->where('status', false)->count() ? true : false;
            })
            ->form([
                Forms\Components\Textarea::make('description')
                    ->label('Nueva notificación')
                    ->placeholder('Escribe una nueva notificación...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn () => Auth::user()->hasRole(['super_admin','admin']))
            ])
            ->action(function (array $data, Employee $record): void {
                EmployeeNote::create([
                    'description' => $data['description'],
                    'employee_id' => $record->id,
                    'user_id' => Auth::id(),
                    'status' => false, // No leída al crear
                ]);

                Notification::make()
                    ->title('Notificación agregada')
                    ->success()
                    ->send();
            })
            ->modalSubmitActionLabel('Agregar notificación')
            ->modalSubmitAction(fn ($action) => $action->visible(fn () => Auth::user()->hasRole(['super_admin','admin'])))
            
            ;
    }
}
