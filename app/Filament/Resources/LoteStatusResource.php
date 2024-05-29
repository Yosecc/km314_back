<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoteStatusResource\Pages;
use App\Filament\Resources\LoteStatusResource\RelationManagers;
use App\Models\loteStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoteStatusResource extends Resource
{
    protected static ?string $model = loteStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Estatus de Lotes';
    protected static ?string $label = 'estatus';
    protected static ?string $navigationGroup = 'Configuración';

    public static function getPluralModelLabel(): string
    {
        return 'estatus';
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__("general.Name"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\ColorPicker::make('color')->label(__("general.Color"))->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__("general.Name"))
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')->label(__("general.Color")),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLoteStatuses::route('/'),
        ];
    }
}
