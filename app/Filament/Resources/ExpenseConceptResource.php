<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExpenseConcept;
use Filament\Resources\Resource;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExpenseConceptResource\Pages;
use App\Filament\Resources\ExpenseConceptResource\RelationManagers;
use App\Models\loteType;

class ExpenseConceptResource extends Resource
{
    protected static ?string $model = ExpenseConcept::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administración contable (vPRUEBA)';
    protected static ?string $label = 'Gasto fijo';
    protected static ?string $pluralLabel = 'Gastos fijos';

    public static function getPluralModelLabel(): string
    {
        return 'Gastos fijos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Repeater::make('expenseConceptLoteType')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('lote_type_id')
                            ->options(loteType::get()->pluck('name','id')->toArray())
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('status')
                    ->required(),
            ]) ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('amount')
                //     ->searchable(),
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
            'index' => Pages\ManageExpenseConcepts::route('/'),
        ];
    }
}
