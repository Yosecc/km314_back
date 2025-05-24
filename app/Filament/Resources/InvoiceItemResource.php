<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceItemResource\Pages;
use App\Filament\Resources\InvoiceItemResource\RelationManagers;
use App\Models\InvoiceItem;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceItemResource extends Resource
{
    protected static ?string $model = InvoiceItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Ítem de Factura';
    protected static ?string $pluralLabel = 'Ítems de Factura';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('invoice_id')
                    ->label('Factura')
                    ->options(function () {
                        return \App\Models\Invoice::with(['owner', 'lote'])->get()->mapWithKeys(function ($invoice) {
                            $owner = $invoice->owner?->nombres() ?? 'Sin propietario';
                            $lote = $invoice->lote?->getNombre() ?? $invoice->lote_id;
                            return [$invoice->id => "#{$invoice->id} - {$owner} - Lote: {$lote}"];
                        })->toArray();
                    })
                    ->searchable()
                    ->required(),
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
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $concept = \App\Models\ExpenseConcept::find($state);
                            if ($concept) {
                                $set('description', $concept->name);
                            }
                        }
                    }),
                TextInput::make('description')
                    ->label('Descripción')
                    ->visible(fn ($get) => $get('is_fixed') != 1)
                    ->required(fn ($get) => $get('is_fixed') != 1),
                TextInput::make('amount')->numeric()->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('invoice.id')->label('Factura'),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->label('Descripción'),
                TextColumn::make('amount')
                    ->money('ARS')
                    ->label('Monto')
                    ->sortable(),
                TextColumn::make('is_fixed')
                    ->formatStateUsing(fn ($state) => $state ? 'Fijo' : 'Variable')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->label('Tipo'),
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
            'index' => Pages\ListInvoiceItems::route('/'),
            'create' => Pages\CreateInvoiceItem::route('/create'),
            'edit' => Pages\EditInvoiceItem::route('/{record}/edit'),
        ];
    }
}
