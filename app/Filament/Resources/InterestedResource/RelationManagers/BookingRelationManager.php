<?php

namespace App\Filament\Resources\InterestedResource\RelationManagers;

use App\Models\Lote;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->label(__('general.Status'))
                    ->relationship(name: 'bookingStatus', titleAttribute: 'name')
                    ->required(),
                Forms\Components\Select::make('lote_id')
                    ->label(__('general.Lote'))
                    ->relationship(
                        name: 'lote',
                        titleAttribute: 'lote_id',
                        modifyQueryUsing: fn (Builder $query) => $query->with('sector'),
                    )
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Lote $record) => "{$record->sector->name} {$record->lote_id}"),
                Forms\Components\Select::make('propertie_id')
                    ->label(__('general.Propertie'))
                    ->searchable()
                    ->relationship(name: 'propertie', titleAttribute: 'identificador'),
                Forms\Components\Select::make('interested_type_operation_id')
                    ->label(__('general.interested_type_operation'))
                    ->relationship(name: 'interested_type_operation', titleAttribute: 'name'),

                Forms\Components\TextInput::make('operation_detail')
                    ->label(__('general.operation_detail'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('amount')
                    ->label(__('general.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(0),

                DatePicker::make('date_end')->label(__('general.date_end'))->required(),
                // interested_id

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_status_id')
            ->columns([
                Tables\Columns\TextColumn::make('bookingStatus.name')->label(__('general.Status')),
                Tables\Columns\TextColumn::make('lote')
                    ->label(__('general.Lote'))
                    ->formatStateUsing(fn (?Lote $state): string => $state
                        ? "{$state->sector->name} {$state->lote_id}"
                        : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('propertie.identificador')
                    ->label(__('general.Propertie'))
                    ->sortable(),
                TextColumn::make('date_end')->label(__('general.date_end'))->date(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
