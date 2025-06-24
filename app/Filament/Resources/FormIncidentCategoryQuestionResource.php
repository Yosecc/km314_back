<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormIncidentCategoryQuestionResource\Pages;
use App\Filament\Resources\FormIncidentCategoryQuestionResource\RelationManagers;
use App\Models\FormIncidentCategoryQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormIncidentCategoryQuestionResource extends Resource
{
    protected static ?string $model = FormIncidentCategoryQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Categorías de preguntas';
    protected static ?string $label = 'Categoría de pregunta';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function getPluralModelLabel(): string
    {
        return 'Categorías de preguntas';
    }

    public static function getModelLabel(): string
    {
        return 'Categoría de pregunta';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la categoría')
                    ->placeholder('Ej: Seguridad, Mantenimiento, Convivencia')
                    ->helperText('Ingrese un nombre descriptivo para la categoría de preguntas.')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data')
            ->inlineLabel(false)
            ->modelLabel('Categoría de pregunta')
            ->description('Gestione las categorías para organizar las preguntas de los formularios de incidentes.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre de la categoría')->searchable(),
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
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionadas'),
                ]),
            ])
            ->emptyStateHeading('No hay categorías de preguntas')
            ->emptyStateDescription('Cree una nueva categoría para comenzar.');
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
            'index' => Pages\ListFormIncidentCategoryQuestions::route('/'),
            'create' => Pages\CreateFormIncidentCategoryQuestion::route('/create'),
            'edit' => Pages\EditFormIncidentCategoryQuestion::route('/{record}/edit'),
        ];
    }
}
