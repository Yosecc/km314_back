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

class FormIncidentQuestionResource extends Resource
{
    protected static ?string $model = FormIncidentQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('form_incident_type_id')
                    ->label('Tipo de formulario')
                    ->relationship('type', 'name')
                    ->required(),
                Forms\Components\Select::make('form_incident_category_question_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('question')
                    ->label('Pregunta')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Tipo de respuesta')
                    ->options([
                        'si_no' => 'Sí/No',
                        'abierta' => 'Abierta',
                        'seleccion_unica' => 'Selección única',
                        'seleccion_multiple' => 'Selección múltiple',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('options')
                    ->label('Opciones (JSON, solo para selección)')
                    ->rows(2)
                    ->nullable(),
                Forms\Components\Toggle::make('required')
                    ->label('¿Respuesta obligatoria?')
                    ->default(true),
                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('question')->label('Pregunta')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo'),
                Tables\Columns\IconColumn::make('required')->label('Obligatoria'),
                Tables\Columns\TextColumn::make('order')->label('Orden'),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
