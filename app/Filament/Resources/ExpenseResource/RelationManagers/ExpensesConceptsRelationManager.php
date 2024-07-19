<?php

namespace App\Filament\Resources\ExpenseResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExpenseConcept;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ExpensesConceptsRelationManager extends RelationManager
{
    protected static string $relationship = 'concepts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('expense_concept_id')
                            ->required()
                            ->options(ExpenseConcept::get()->pluck('name', 'id')->toArray()),
                            
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),  
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_concept_id')
            ->columns([
                Tables\Columns\TextColumn::make('expense_concept_id'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
