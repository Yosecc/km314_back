<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountStatusResource\Pages;
use App\Filament\Resources\AccountStatusResource\RelationManagers;
use App\Models\AccountStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\NumberColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountStatusResource extends Resource
{
    protected static ?string $model = AccountStatus::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Estado de Cuenta';
    protected static ?string $pluralLabel = 'Estados de Cuenta';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('owner.first_name')->label('Propietario'),
                TextColumn::make('balance')->numeric()->label('Saldo'),
                TextColumn::make('total_invoiced')->numeric()->label('Total Facturado'),
                TextColumn::make('total_paid')->numeric()->label('Total Pagado'),
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
            'index' => Pages\ListAccountStatuses::route('/'),
            'create' => Pages\CreateAccountStatus::route('/create'),
            'edit' => Pages\EditAccountStatus::route('/{record}/edit'),
        ];
    }
}
