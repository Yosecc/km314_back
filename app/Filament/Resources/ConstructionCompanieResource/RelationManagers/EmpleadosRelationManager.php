<?php

namespace App\Filament\Resources\ConstructionCompanieResource\RelationManagers;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeOrigen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmpleadosRelationManager extends RelationManager
{
    protected static string $relationship = 'empleados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Trabajador')
                    ->options(fn () => Employee::orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn ($e) => [$e->id => "{$e->first_name} {$e->last_name} (DNI: {$e->dni})"])
                    )
                    ->searchable()
                    ->required(),

                Forms\Components\Hidden::make('model')
                    ->default('ConstructionCompanie'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['employee' => fn ($q) => $q->withTrashed()]))
            ->recordTitleAttribute('employee.first_name')
            ->columns([
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->getStateUsing(fn (EmployeeOrigen $record) => $record->employee?->dni)
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->getStateUsing(fn (EmployeeOrigen $record) => $record->employee?->first_name)
                    ->color(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['color'] : null)
                    ->tooltip(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['texto'] : null)
                    ->searchable()
                    ->suffix(fn (EmployeeOrigen $record) => $record->employee?->trashed() ? ' ⚠ Eliminado' : null),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->getStateUsing(fn (EmployeeOrigen $record) => $record->employee?->last_name)
                    ->color(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['color'] : null)
                    ->tooltip(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['texto'] : null)
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->label('Vincular trabajador existente'),
                Tables\Actions\Action::make('crear_empleado')
                    ->label('Crear nuevo trabajador')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->url(fn (): string => EmployeeResource::getUrl('create') . '?companie_id=' . $this->getOwnerRecord()->id),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn (EmployeeOrigen $record) => $record->employee_id
                        ? EmployeeResource::getUrl('edit', ['record' => $record->employee_id])
                        : null),
                Tables\Actions\Action::make('restaurar')
                    ->label('Restaurar trabajador')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (EmployeeOrigen $record) => $record->employee?->trashed())
                    ->requiresConfirmation()
                    ->action(function (EmployeeOrigen $record) {
                        $record->employee->restore();
                        Notification::make()
                            ->title('Trabajador restaurado correctamente.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()->label('Borrar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
