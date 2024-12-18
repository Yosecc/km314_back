<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerStatusResource\Pages;
use App\Filament\Resources\OwnerStatusResource\RelationManagers;
use App\Models\OwnerStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
class OwnerStatusResource extends Resource
{
    protected static ?string $model = OwnerStatus::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Estados de propietarios';
    protected static ?string $label = 'estado del propietario';
    protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                ColorPicker::make('color')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color'),
                TextColumn::make('name')
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
            'index' => Pages\ListOwnerStatuses::route('/'),
            'create' => Pages\CreateOwnerStatus::route('/create'),
            'edit' => Pages\EditOwnerStatus::route('/{record}/edit'),
        ];
    }
}
