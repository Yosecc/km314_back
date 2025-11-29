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
            ->badge(fn (Employee $record) => $record->notes()->where('status', false)->count() ?: null)
            ->badgeColor('danger')
            ->modalHeading(fn ($record) => 'Notificaciones - ' . $record->first_name . ' ' . $record->last_name)
            ->modalWidth('3xl')
            ->modalContent(fn (Employee $record) => view('filament.components.employee-notes', [
                'notes' => $record->notes()->with('user')->orderBy('created_at', 'desc')->get(),
                'employee' => $record
            ]))
            ->mountUsing(function (Employee $record) {
                // Marcar todas las notas como leídas al abrir el modal
                $record->notes()->where('status', false)->update(['status' => true]);
            })
            ->form([
                Forms\Components\Textarea::make('description')
                    ->label('Nueva notificación')
                    ->placeholder('Escribe una nueva notificación...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
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
            ->modalSubmitActionLabel('Agregar notificación');
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
            ->badge(fn (Employee $record) => $record->notes()->where('status', false)->count() ?: null)
            ->badgeColor('danger')
            ->modalHeading(fn (Employee $record) => 'Notificaciones - ' . $record->first_name . ' ' . $record->last_name)
            ->modalWidth('3xl')
            ->modalContent(fn (Employee $record) => view('filament.components.employee-notes', [
                'notes' => $record->notes()->with('user')->orderBy('created_at', 'desc')->get(),
                'employee' => $record
            ]))
            ->mountUsing(function (Employee $record) {
                // Marcar todas las notas como leídas al abrir el modal
                $record->notes()->where('status', false)->update(['status' => true]);
            })
            ->form([
                Forms\Components\Textarea::make('description')
                    ->label('Nueva notificación')
                    ->placeholder('Escribe una nueva notificación...')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
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
            ->modalSubmitActionLabel('Agregar notificación');
    }
}
