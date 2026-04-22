<?php

namespace App\Filament\Resources\ConstructionCompanieResource\RelationManagers;

use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeOrigen;
use Filament\Forms;
use Filament\Forms\Form;
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
            ->modifyQueryUsing(fn ($query) => $query->with('employee'))
            ->recordTitleAttribute('employee.first_name')
            ->columns([
                Tables\Columns\TextColumn::make('employee.dni')
                    ->label(__("general.DNI"))
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__("general.FirstName"))
                    ->color(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['color'] : null)
                    ->tooltip(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['texto'] : null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.last_name')
                    ->label(__("general.LastName"))
                    ->color(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['color'] : null)
                    ->tooltip(fn (EmployeeOrigen $record) => $record->employee ? EmployeeResource::isVencimientos($record->employee)['texto'] : null)
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Vincular trabajador'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn (EmployeeOrigen $record) => $record->employee_id
                        ? EmployeeResource::getUrl('edit', ['record' => $record->employee_id])
                        : null),
                Tables\Actions\DeleteAction::make()
                    ->label('Desvincular'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
