<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormIncidentQuestionResource\Pages;
use App\Filament\Resources\FormIncidentQuestionResource\RelationManagers;
use App\Models\FormIncidentQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TagsInput;
class FormIncidentQuestionResource extends Resource
{
    protected static ?string $model = FormIncidentQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Preguntas de incidentes';
    protected static ?string $label = 'Pregunta de incidente';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function getPluralModelLabel(): string
    {
        return 'Preguntas de incidentes';
    }

    public static function getModelLabel(): string
    {
        return 'Pregunta de incidente';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('types')
                    ->label('Tipos de formulario')
                    ->placeholder('Seleccione uno o más tipos')
                    ->helperText('Seleccione los tipos de formulario donde estará disponible esta pregunta.')
                    ->relationship('types', 'name')
                    ->multiple()
                    ->required(),
                Forms\Components\Select::make('categories')
                    ->label('Categorías')
                    ->placeholder('Seleccione una o más categorías')
                    ->helperText('Seleccione las categorías a las que pertenece la pregunta.')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->required(),
                Forms\Components\RichEditor::make('question')
                    ->label('Pregunta')
                    ->placeholder('Ingrese el texto de la pregunta')
                    ->helperText('Redacte la pregunta que se mostrará en el formulario de incidente.')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Tipo de respuesta')
                    ->options([
                        'si_no' => 'Sí/No',
                        'abierta' => 'Abierta',
                        'seleccion_unica' => 'Selección única',
                        'seleccion_multiple' => 'Selección múltiple',
                    ])
                    ->helperText('Seleccione el tipo de respuesta que se espera para esta pregunta.')
                    ->live()
                    ->required(),

                Forms\Components\TagsInput::make('options')
                    ->label('Opciones')
                    ->placeholder('Ingrese las opciones y presione Enter')
                    ->helperText('Solo para selección única o múltiple. Ingrese cada opción y presione Enter.')
                    ->live()
                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['seleccion_unica', 'seleccion_multiple']))
                    ->nullable(),
                Forms\Components\Toggle::make('required')
                    ->label('¿Respuesta obligatoria?')
                    ->helperText('Indique si esta pregunta debe ser respondida obligatoriamente.')
                    ->default(true),
                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->placeholder('Ej: 1, 2, 3...')
                    ->helperText('Indique el orden en que aparecerá la pregunta en el formulario.')
                    ->numeric()
                    ->nullable(),
            ])
            ->statePath('data')
            ->inlineLabel(false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('question')->label('Pregunta')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo de respuesta'),
                Tables\Columns\TextColumn::make('types.name')->label('Tipos de formulario')->limit(2),
                Tables\Columns\TextColumn::make('categories.name')->label('Categorías')->limit(2),
                Tables\Columns\IconColumn::make('required')->label('Obligatoria'),
                Tables\Columns\TextColumn::make('order')->label('Orden'),
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
            ->emptyStateHeading('No hay preguntas de incidentes')
            ->emptyStateDescription('Cree una nueva pregunta para comenzar.');
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
            'index' => Pages\ListFormIncidentQuestions::route('/'),
            'create' => Pages\CreateFormIncidentQuestion::route('/create'),
            'edit' => Pages\EditFormIncidentQuestion::route('/{record}/edit'),
        ];
    }
}
