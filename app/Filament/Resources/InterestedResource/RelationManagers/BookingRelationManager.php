<?php

namespace App\Filament\Resources\InterestedResource\RelationManagers;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class BookingRelationManager extends RelationManager
{
    protected static string $relationship = 'booking';

    protected static ?string $label = 'reservación';
    protected static ?string $title = 'Reservación';

    public static function getPluralModelLabel(): string
    {
        return 'reservaciones';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('booking_status_id')
                    ->label(__("general.Status"))
                    ->relationship(name: 'bookingStatus', titleAttribute: 'name')
                    ->required(),
                Forms\Components\Select::make('lote_id')
                    ->label(__("general.Lote"))
                    ->relationship(name: 'lote',titleAttribute: 'lote_id')
                    ->getOptionLabelFromRecordUsing(fn (Lote $record) => "{$record->sector->name} {$record->lote_id}"),
                Forms\Components\Select::make('propertie_id')
                    ->label(__("general.Propertie"))
                    ->searchable()
                    ->relationship(name: 'propertie', titleAttribute: 'identificador'),
                Forms\Components\Select::make('interested_type_operation_id')
                    ->label(__("general.interested_type_operation"))
                    ->relationship(name: 'interested_type_operation', titleAttribute: 'name'),

                Forms\Components\TextInput::make('operation_detail')
                    ->label(__('general.operation_detail'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('amount')
                    ->label(__('general.amount'))
                    ->numeric()
                    ->required()
                    ->maxLength(255),

                DatePicker::make('date_end')->label(__('general.date_end'))->required()
// interested_id


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_status_id')
            ->columns([
                Tables\Columns\TextColumn::make('bookingStatus.name')->label(__("general.Status")),
                Tables\Columns\TextColumn::make('lote')
                    ->label(__("general.Lote"))
                    ->formatStateUsing(fn (Lote $state) => "{$state->sector->name}{$state->lote_id}" )
                    ->sortable(),
                Tables\Columns\TextColumn::make('propertie.identificador')
                    ->label(__("general.Propertie"))
                    ->sortable(),
                TextColumn::make('date_end')->label(__('general.date_end'))->dateTime()
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
