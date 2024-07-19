<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Expense;
use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExpenseConcept;
use App\Models\ExpensesConcepts;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\ExpenseResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExpenseResource\RelationManagers;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Expensas';
    protected static ?string $label = 'expensa';
    protected static ?string $navigationGroup = 'Administracion Contable';

    
    public static function getPluralModelLabel(): string
    {
        return 'Expensas';
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                

                Forms\Components\Select::make('lote_id')
                    ->label(__("general.Lotes"))
                    ->options(Lote::get()->map(function($lote){
                        $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                        return $lote;
                    })
                    ->pluck('lote_name', 'id')->toArray()),

                Forms\Components\Select::make('propertie_id')
                    ->label(__("general.Propertie"))
                    ->options(Property::get()->pluck('identificador', 'id')->toArray()),
                
                Forms\Components\Select::make('owner_id')
                    ->relationship(name: 'owner')
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    ->label(__("general.Owner")),
                    

                Forms\Components\Select::make('expense_status_id')
                    ->required()
                    ->relationship(name: 'expenseStatus', titleAttribute: 'name'),
               

               
                Forms\Components\DatePicker::make('date_prox_payment')
                    ->required(),

                

                // Forms\Components\TextInput::make('')
                // ->afterStateHydrated(function (TextInput $component, string $state,  $record) {
                //     // dd('sl',);
                //     $component->state($record->concepts->sum('amount'));
                //     // return 'kdkdk';
                // })->disabled()
                // Forms\Components\Repeater::make('expensesConcepts')
                //     ->schema([
                //         Forms\Components\Select::make('expense_concept_id')
                //             ->required()
                //             ->options(ExpenseConcept::get()->pluck('name', 'id')->toArray()),
                            
                //         Forms\Components\TextInput::make('amount')
                //             ->required()
                //             ->maxLength(255),
                        
                //         Forms\Components\TextInput::make('description')
                //             ->required()
                //             ->maxLength(255),  
                            
                //     ])
                //     ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('expenseStatus.color')->label(''),
                Tables\Columns\TextColumn::make('expenseStatus.name')->label('Estatus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('concepts.expenseConcept.name')
                    ->badge()
,
                Tables\Columns\TextColumn::make('lote')
                    ->formatStateUsing(fn ($state) => $state->sector->name.$state->lote_id )
                    ->sortable(),
                Tables\Columns\TextColumn::make('propertie.identificador')
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner')
                    ->formatStateUsing(fn ($state) => $state->first_name.' '.$state->last_name )
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('concepts')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($record, $state) => $record->concepts->sum('amount'))
                    // ->numeric()
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_prox_payment')
                    ->date()
                    ->sortable(),
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
            RelationManagers\ConceptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
