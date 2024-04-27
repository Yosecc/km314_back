<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoteResource\Pages;
use App\Filament\Resources\LoteResource\RelationManagers;
use App\Models\Lote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoteResource extends Resource
{
    protected static ?string $model = Lote::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('width')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('height')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('m2')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('sector_id')
                    ->required()
                    ->relationship(name: 'sector', titleAttribute: 'name'),
                Forms\Components\TextInput::make('lote_id')
                    ->required()
                    ->numeric(),
                
                Forms\Components\Select::make('lote_type_id')
                    ->required()
                    ->relationship(name: 'loteType', titleAttribute: 'name'),
                Forms\Components\Select::make('lote_status_id')
                    ->required()
                    ->relationship(name: 'loteStatus', titleAttribute: 'name'),
                Forms\Components\Select::make('owner_id')
                    ->required()
                    ->relationship(name: 'owner', titleAttribute: 'last_name'),
                Forms\Components\Textarea::make('ubication')
                    ->columnSpanFull(),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('loteStatus.color')->label('Status'),
                Tables\Columns\TextColumn::make('sector.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lote_id')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('loteStatus.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loteType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('width')
                    ->searchable(),
                Tables\Columns\TextColumn::make('height')
                    ->searchable(),
                Tables\Columns\TextColumn::make('m2')
                    ->searchable(),
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
            'index' => Pages\ManageLotes::route('/'),
        ];
    }
}
