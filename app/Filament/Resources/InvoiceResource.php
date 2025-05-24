<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

       protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Factura';
    protected static ?string $pluralLabel = 'Facturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
         Select::make('owner_id')
                    ->relationship('owner', 'first_name')
                    ->required(),
                Select::make('lote_id')
                    ->relationship('lote', 'id')
                    ->required(),
                DatePicker::make('period')->required(),
                TextInput::make('total')->numeric()->required(),
                Select::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'pagada' => 'Pagada',
                        'vencida' => 'Vencida',
                    ])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 TextColumn::make('id')->sortable(),
                TextColumn::make('owner.first_name')->label('Propietario'),
                TextColumn::make('lote.id')->label('Lote'),
                TextColumn::make('period')->date('F Y')->label('Periodo'),
                TextColumn::make('total')->numeric()->label('Total'),
                TextColumn::make('status')
                    ->badge()
                    ->enum([
                        'pendiente' => 'Pendiente',
                        'pagada' => 'Pagada',
                        'vencida' => 'Vencida',
                    ])
                    ->colors([
                        'secondary' => 'pendiente',
                        'success' => 'pagada',
                        'danger' => 'vencida',
                    ]),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
