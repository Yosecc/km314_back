<?php

namespace App\Filament\Resources\ExpenseResource\RelationManagers;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExpenseConcept;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ConceptsRelationManager extends RelationManager
{
    protected static string $relationship = 'concepts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('lote_id')
                    ->label(__("general.Lotes"))
                    ->options(function(RelationManager $livewire){
                        return Lote::where('owner_id',$livewire->getOwnerRecord()->owner_id)->get()->map(function($lote){
                            $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                            return $lote;
                        })
                        ->pluck('lote_name', 'id')->toArray();
                    })
                    ->afterStateUpdated(function(RelationManager $livewire, $state){
                        // dd($state,$livewire->getOwnerRecord()); 
                        // $lote = Lote::where('id', $state)->first();
                        // 
                    })
                    ->live(),

                Forms\Components\Select::make('expense_concept_id')
                    ->required()
                    ->options(ExpenseConcept::get()->pluck('name', 'id')->toArray())
                    ->afterStateUpdated(function($state, Get $get, Set $set){
                        $lote = Lote::where('id', $get('lote_id'))->first();
                        if(!$lote){

                            Notification::make()
                                ->title('El lote es requerido')
                                ->danger()
                                ->send();   
                                $set('expense_concept_id',null);
                                return;
                        }
                        
                        $concepto = ExpenseConcept::where('id',$state)->with(['expenseConceptLoteType'])->first();
                        $ECLT = $concepto->expenseConceptLoteType->where('lote_type_id',$lote->loteType->id)->first();
                        $set('amount',$ECLT->amount);
                        // $set('xmetro',$ECLT->amount);
                        $set('description',$concepto->name);
                    })
                    ->live(),     
                    Forms\Components\Grid::make([
                            'default' => 3,
                        ])
                        ->schema([
                            Forms\Components\Toggle::make('is_metro_cuadrado')
                                ->label('Calcular x m2')
                                ->afterStateUpdated(function($state, Set $set, Get $get){
                                    $lote = Lote::where('id', $get('lote_id'))->first();
                                    if(!$lote){
                                        Notification::make()
                                            ->title('El lote es requerido')
                                            ->danger()
                                            ->send();   
                                            $set('expense_concept_id',null);
                                            return;
                                    }
                                    if($state){
                                        $set('m2', $lote->m2);
                                        $set('description',$get('description').' (x m2)');
                                        $amount = intval($get('m2')) * intval($get('xmetro'));
                                        $set('amount',$amount);
                                    }else{
                                        $concepto = ExpenseConcept::where('id',$get('expense_concept_id'))->with(['expenseConceptLoteType'])->first();
                                        $ECLT = $concepto->expenseConceptLoteType->where('lote_type_id',$lote->loteType->id)->first();
                                        $set('amount',$ECLT->amount);
                                        $set('description',$concepto->name);
                                    }
                                })
                                ->live(),
                            
                            Forms\Components\TextInput::make('m2')
                                ->required()
                                ->numeric()
                                ->disabled(),
                            
                            Forms\Components\TextInput::make('xmetro')
                                ->label('Precio x m2')
                                ->required()
                                ->numeric()
                                ->afterStateUpdated(function(Get $get, Set $set, $state){
                                    // dd(intval($get('m2')) . intval($state));
                                    $amount = intval($get('m2')) * intval($state);
                                    $set('amount',$amount);
                                })
                                ->disabled(function(Get $get){
                                    return !$get('is_metro_cuadrado');
                                })
                                ->required(function(Get $get){
                                    return $get('is_metro_cuadrado');
                                })
                                ->live(),
                        ]),
               
                
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->maxLength(255),

                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),

                

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_id')
            ->columns([
                Tables\Columns\TextColumn::make('expenseConcept.name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('lote_id')
                    ->formatStateUsing(function($state){
                        // dd($state);
                        $lote = Lote::where('id', $state)->first();
                        return $lote->sector->name.$lote->lote_id;
                    } ),
                Tables\Columns\TextColumn::make('amount')
                                            ->numeric()
                                            ->summarize(Sum::make())

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
