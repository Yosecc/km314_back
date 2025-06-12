<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceConfigResource\Pages;
use App\Filament\Resources\InvoiceConfigResource\RelationManagers;
use App\Models\InvoiceConfig;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceConfigResource extends Resource
{
    protected static ?string $model = InvoiceConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Facturación Mensual';
    protected static ?string $pluralLabel = 'Facturaciónes Mensuales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('period')
                    ->label('Periodo')
                    ->required()
                    ->displayFormat('F Y'),
                Forms\Components\DatePicker::make('fecha_creacion')
                    ->label('Fecha de ejecución')
                    ->required(),

                        Forms\Components\Builder::make('config')
                            ->label('Configuración de Facturación')
                            ->blocks([

                                Forms\Components\Builder\Block::make('items_invoice')
                                    // ->label('Ítem de factura')
                                    ->schema([

                                    Section::make('Ítem de factura')
                                        ->description('Configura los items a cobrarse en esta facturación mensual')
                                        ->schema([
                                            Repeater::make(name: 'items')
                                                ->schema([
                                                    Select::make('is_fixed')
                                                        ->options([
                                                            1 => 'Fijo',
                                                            0 => 'Variable',
                                                        ])
                                                        ->required()
                                                        ->live(),
                                                    Select::make('expense_concept_id')
                                                        ->label('Concepto fijo')
                                                        ->options(\App\Models\ExpenseConcept::pluck('name', 'id'))
                                                        ->visible(fn ($get) => $get('is_fixed') == 1)
                                                        ->required(fn ($get) => $get('is_fixed') == 1)
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Set $set) {
                                                            if ($state) {
                                                                $concept = \App\Models\ExpenseConcept::find($state);
                                                                if ($concept) {
                                                                    $set('description', $concept->name);
                                                                }
                                                            }
                                                        }),
                                                    TextInput::make('description')
                                                        ->label('Descripción')
                                                        ->live()
                                                        ->required(fn ($get) => $get('is_fixed') != 1),
                                                    TextInput::make('amount')->numeric()->required(),
                                                ])
                                                ->columns(2),

                                        ])
                                        ->columns(1),

                            ])
                            ->minItems(1)
                            ->addActionLabel('Agregar ítem')
                            ->blockNumbers(false),


                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')->label('Periodo')->date('F Y'),
                Tables\Columns\TextColumn::make('fecha_creacion')->label('Fecha de ejecución')->date(),
                Tables\Columns\TextColumn::make('config')
                    ->label('Configuración')
                    ->formatStateUsing(fn($state) => is_array($state) ? count($state).' ítems' : 'Sin configuración'),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
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
            'index' => Pages\ListInvoiceConfigs::route('/'),
            'create' => Pages\CreateInvoiceConfig::route('/create'),
            'edit' => Pages\EditInvoiceConfig::route('/{record}/edit'),
        ];
    }
}
