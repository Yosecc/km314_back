<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingStatusResource\Pages;
use App\Filament\Resources\BookingStatusResource\RelationManagers;
use App\Models\BookingStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingStatusResource extends Resource
{
    protected static ?string $model = BookingStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Estados de reserva';
    protected static ?string $label = 'Estado de reserva';
    protected static ?string $navigationGroup = 'Configuración';

    
    public static function getPluralModelLabel(): string
    {
        return 'Estado de reservas';
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
            'index' => Pages\ManageBookingStatuses::route('/'),
        ];
    }
}
