<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormIncidentTypeResource\Pages;
use App\Filament\Resources\FormIncidentTypeResource\RelationManagers;
use App\Models\FormIncidentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormIncidentTypeResource extends Resource
{
    protected static ?string $model = FormIncidentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';
    protected static ?string $navigationLabel = 'Tipos de formularios';
    protected static ?string $label = 'Tipo de formulario';

    public static function getPluralModelLabel(): string
    {
        return 'Tipos de formularios';
    }

    public static function getModelLabel(): string
    {
        return 'Tipo de formulario';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del tipo de formulario')
                    ->placeholder('Ej: Reporte de accidente, Queja, Sugerencia')
                    ->helperText('Ingrese un nombre descriptivo para el tipo de formulario de incidente.')
                    ->required()
                    ->maxLength(255),
            ])
            ->columns(1)
            ->statePath('data')
            ->inlineLabel(false)
            ->description('Gestione los diferentes tipos de formularios de incidentes disponibles en el sistema.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre del tipo de formulario')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha de creación')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->emptyStateHeading('No hay tipos de formularios')
            ->emptyStateDescription('Cree un nuevo tipo de formulario para comenzar.');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormIncidentTypes::route('/'),
            'create' => Pages\CreateFormIncidentType::route('/create'),
            'edit' => Pages\EditFormIncidentType::route('/{record}/edit'),
        ];
    }
}
