<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TerminosCondicionesResource\Pages;
use App\Filament\Resources\TerminosCondicionesResource\RelationManagers;
use App\Models\TerminosCondiciones;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use FilamentTiptapEditor\TiptapEditor;

class TerminosCondicionesResource extends Resource
{
    protected static ?string $model = TerminosCondiciones::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TiptapEditor::make('contenido')
                    ->required()
                    ->profile('default')
                    ->tools([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'heading',
                        'bullet-list',
                        'ordered-list',
                        'blockquote',
                        'hr',
                        'align-left',
                        'align-center',
                        'align-right',
                        'table',
                    ])
                    ->extraInputAttributes(['style' => 'min-height: 24rem;'])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->label('Título')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable(),
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
            'index' => Pages\ManageTerminosCondiciones::route('/'),
        ];
    }
}
