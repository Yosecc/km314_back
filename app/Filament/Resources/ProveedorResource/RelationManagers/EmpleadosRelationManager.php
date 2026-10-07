<?php

namespace App\Filament\Resources\ProveedorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmpleadosRelationManager extends RelationManager
{
    protected static string $relationship = 'empleados';
    protected static ?string $title = 'Empleados del proveedor';
    protected static ?string $modelLabel = 'empleado';
    protected static ?string $pluralModelLabel = 'empleados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('apellido')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('dni')
                    ->label('DNI')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('archivo_dni')
                    ->label('Archivo del DNI')
                    ->openable()
                    ->downloadable()
                    ->getUploadedFileNameForStorageUsing(
                        fn ($file, $record) => $file ? $file->getClientOriginalName() : $record?->archivo_dni
                    ),
                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apellido')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('Sin informar'),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('Sin informar'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar empleado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
