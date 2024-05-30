<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterestedOriginResource\Pages;
use App\Filament\Resources\InterestedOriginResource\RelationManagers;
use App\Models\InterestedOrigin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InterestedOriginResource extends Resource
{
    protected static ?string $model = InterestedOrigin::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Origen de interesados';
    protected static ?string $label = 'Origen de interesado';
    protected static ?string $navigationGroup = 'Configuración';

    
    public static function getPluralModelLabel(): string
    {
        return 'Origen de interesados';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__("general.Name"))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__("general.Name"))
                    ->searchable(),
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
            'index' => Pages\ManageInterestedOrigins::route('/'),
        ];
    }
}
