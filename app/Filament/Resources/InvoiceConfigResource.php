<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceConfigResource\Pages;
use App\Filament\Resources\InvoiceConfigResource\RelationManagers;
use App\Models\InvoiceConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceConfigResource extends Resource
{
    protected static ?string $model = InvoiceConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Forms\Components\Repeater::make('config')
                    ->label('Configuración de ítems')
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label('Descripción')
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required(),
                        Forms\Components\Toggle::make('is_fixed')
                            ->label('¿Es fijo?')
                            ->default(true),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columns(3)
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
