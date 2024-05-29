<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigWebResource\Pages;
use App\Filament\Resources\ConfigWebResource\RelationManagers;
use App\Models\configWeb;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConfigWebResource extends Resource
{
    protected static ?string $model = configWeb::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $recordTitleAttribute = 'name';


    protected static ?string $navigationLabel = 'Configuración';
    protected static ?string $label = 'configuración';
    protected static ?string $navigationGroup = 'Web';

    public static function getPluralModelLabel(): string
    {
        return 'configuraciones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name'),
                Forms\Components\TextInput::make('value'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('value'),
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
            'index' => Pages\ListConfigWebs::route('/'),
            'create' => Pages\CreateConfigWeb::route('/create'),
            'edit' => Pages\EditConfigWeb::route('/{record}/edit'),
        ];
    }
}
