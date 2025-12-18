<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilesRequiredResource\Pages;
use App\Filament\Resources\FilesRequiredResource\RelationManagers;
use App\Models\FilesRequired;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FilesRequiredResource extends Resource
{
    protected static ?string $model = FilesRequired::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'car' => 'Auto',
                        'employee' => 'Empleado',
                    ]),
                Forms\Components\TagsInput::make('required')->label('Documentos requeridos'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Tipo')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('required')->label('Documentos requeridos')->limit(50)->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
    // public static function canCreate(): bool
    // {
    //     return false;
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFilesRequireds::route('/'),
        ];
    }
}
