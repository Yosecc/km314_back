<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterestedTypeResource\Pages;
use App\Models\InterestedType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InterestedTypeResource extends Resource
{
    protected static ?string $model = InterestedType::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Tipos de interés';
    protected static ?string $label = 'tipo de interés';
    protected static ?string $navigationGroup = 'Configuración';

    public static function getPluralModelLabel(): string
    {
        return 'tipos de interés';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInterestedTypes::route('/')];
    }
}
