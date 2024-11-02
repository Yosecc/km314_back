<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingResource\Pages;
use App\Filament\Resources\LandingResource\RelationManagers;
use App\Models\Landing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;

class LandingResource extends Resource
{
    protected static ?string $model = Landing::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->maxLength(255),
                Forms\Components\TextInput::make('subtitle')
                    ->maxLength(255),
                Forms\Components\TextInput::make('btnactioname')
                    ->maxLength(255),
                Forms\Components\TextInput::make('btnactiomessage')
                    ->maxLength(255),
                Forms\Components\Toggle::make('status')
                    ->required(),
                RichEditor::make('content'),

                    Repeater::make('imagenes')
                    ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('img')
                        ])
                        ->columns(2),
                    Repeater::make('campos')
                    ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('type')->maxLength(255),
                            Forms\Components\TextInput::make('name')->maxLength(255),
                            Forms\Components\TextInput::make('label')->maxLength(255),
                            Forms\Components\TextInput::make('placeholder')->maxLength(255),
                        ])
                        ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('btnactioname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('btnactiomessage')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLandings::route('/'),
            'create' => Pages\CreateLanding::route('/create'),
            'edit' => Pages\EditLanding::route('/{record}/edit'),
        ];
    }
}
