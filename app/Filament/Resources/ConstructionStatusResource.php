<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConstructionStatusResource\Pages;
use App\Filament\Resources\ConstructionStatusResource\RelationManagers;
use App\Models\ConstructionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConstructionStatusResource extends Resource
{
    protected static ?string $model = ConstructionStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Estado de construcciones';
    protected static ?string $label = 'estado de construcción';
    protected static ?string $navigationGroup = 'Configuracion de construcciones';

    
    public static function getPluralModelLabel(): string
    {
        return 'estado de construcciones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label(__("general.Name"))
                    ->maxLength(255),
                Forms\Components\ColorPicker::make('color')->label(__("general.Color"))->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label(__("general.Name"))
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')->label(__("general.Color")),
                Tables\Columns\TextColumn::make('created_at')
                ->label(__("general.created_at"))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                
                
                
                
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
            'index' => Pages\ManageConstructionStatuses::route('/'),
        ];
    }
}
