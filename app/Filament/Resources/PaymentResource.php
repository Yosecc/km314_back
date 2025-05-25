<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Owner;
use App\Models\Payment;
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
use Filament\Tables\Grouping\Group;
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Pago';
    protected static ?string $pluralLabel = 'Pagos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('owner_id')
                    ->relationship('owner', 'first_name')
                    ->label('Propietario')
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->live()
                    ->required(),
                Select::make('invoice_id')
                    ->label('Factura a pagar')
                    ->options(function ($get) {
                        $ownerId = $get('owner_id');
                        if (!$ownerId) return [];
                        $invoices = \App\Models\Invoice::where('owner_id', $ownerId)
                            ->whereIn('status', ['pendiente', 'vencida'])
                            ->get()
                            ->mapWithKeys(fn($inv) => [
                                $inv->id => "#{$inv->public_identifier} - Periodo: " . \Carbon\Carbon::parse($inv->period)->format('m/Y') . " - Lote: {$inv->lote?->getNombre()} - Monto: {$inv->total} - {$inv->status}"
                            ])->toArray();
                        return $invoices;
                    })
                    ->required()
                    ->disabled(fn ($get) => !$get('owner_id'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $invoice = \App\Models\Invoice::find($state);
                            if ($invoice) {
                                $set('amount', $invoice->total);
                            }
                        }
                    }),
                TextInput::make('amount')
                    ->numeric()
                    ->label('Monto a pagar')
                    ->required(),
                DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),
                TextInput::make('method'),
                TextInput::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(
                Group::make('owner.first_name')
                        ->label('Propietario')
                        ->getTitleFromRecordUsing(fn (Payment $record) => "{$record->owner->nombres()}")
            )
            ->columns([
                // TextColumn::make('id')->sortable(),
                TextColumn::make('owner.first_name')
                    ->formatStateUsing(fn (Payment $record) => "{$record->owner->nombres()}")
                    ->label('Propietario'),
                TextColumn::make('amount')->numeric()->label('Monto'),
                TextColumn::make('payment_date')->date(),
                TextColumn::make('method'),
                TextColumn::make('notes'),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
