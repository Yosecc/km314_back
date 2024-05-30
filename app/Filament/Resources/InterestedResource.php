<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use App\Models\Property;
use Filament\Forms\Form;
use App\Models\Interested;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InterestedResource\Pages;
use App\Filament\Resources\InterestedResource\RelationManagers;

class InterestedResource extends Resource
{
    protected static ?string $model = Interested::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Interesados';
    protected static ?string $label = 'interesado';
    // protected static ?string $navigationGroup = 'Configuración';

    
    public static function getPluralModelLabel(): string
    {
        return 'interesados';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('dni')
                    ->label(__("general.DNI"))
                    ->numeric(),
                Forms\Components\TextInput::make('first_name')
                    ->label(__("general.FirstName"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label(__("general.LastName"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__("general.Email"))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__("general.Phone"))
                    ->tel()
                    ->numeric(),
                Forms\Components\TextInput::make('address')
                    ->label(__("general.Address"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('city')
                    ->label(__("general.City"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('state')
                    ->label(__("general.State"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip_code')
                    ->label(__("general.ZipCode"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label(__("general.Country"))
                    ->maxLength(255),
                Forms\Components\Select::make('interested_origins_id')
                    ->label(__("general.interested_origins_id"))
                    ->relationship(name: 'interestedOrigins', titleAttribute: 'name')
                    ->required(),
                Forms\Components\Select::make('lote_id')
                    ->label(__("general.Lote"))
                    ->relationship(name: 'lote',titleAttribute: 'lote_id')
                    // ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Lote $record) => "{$record->sector->name} {$record->lote_id}"),
                Forms\Components\Select::make('propertie_id')
                    ->label(__("general.Propertie"))
                    ->searchable()
                    ->relationship(name: 'propertie', titleAttribute: 'identificador')
                    // ->getOptionLabelFromRecordUsing(fn (Property $record) => "{$record->propertyType->name} - {$record->owner->first_name} {$record->owner->last_name}"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__("general.Email"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__("general.Phone"))
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lote')
                    ->label(__("general.Lote"))
                    ->formatStateUsing(fn (Lote $state) => "{$state->sector->name}{$state->lote_id}" )
                    ->sortable(),
                Tables\Columns\TextColumn::make('propertie.identificador')
                    ->label(__("general.Propertie"))
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__("general.created_at"))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),  
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInteresteds::route('/'),
            'create' => Pages\CreateInterested::route('/create'),
            'edit' => Pages\EditInterested::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
       return [
           RelationManagers\BookingRelationManager::class,
       ];
    }
}
