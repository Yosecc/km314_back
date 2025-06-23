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

    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la categoría')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
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
            'index' => Pages\ListFormIncidentCategoryQuestions::route('/'),
            'create' => Pages\CreateFormIncidentCategoryQuestion::route('/create'),
            'edit' => Pages\EditFormIncidentCategoryQuestion::route('/{record}/edit'),
        ];
    }
}
