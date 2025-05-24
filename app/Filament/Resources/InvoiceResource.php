<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Lote;
use App\Models\Owner;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                Select::make('owner_id')
                    ->relationship('owner', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->label('Propietario')
                    ->live()
                    ->required(),
                Select::make('lote_id')
                    ->label('Lote')
                    ->options(function (Get $get) {
                        $ownerId = $get('owner_id');
                        if (!$ownerId) return [];
                        $lotes = Lote::where('owner_id', $ownerId)->get();
                        // Cambia el orden de los argumentos para que el label sea el valor mostrado
                        return $lotes->mapWithKeys(function ($lote) {
                            return [
                                $lote->id => "{$lote->sector->name} {$lote->lote_id}"
                            ];
                        });
                    })
                    ->required()
                    ->disabled(fn (Get $get) => !$get('owner_id')),
                DatePicker::make('period')
                    ->required()
                    ->displayFormat('F Y')
                    ->format('Y-m-01')
                    ->rules([
                        function (Get $get) {
                            return function ($attribute, $value, $fail) use ($get) {
                                $ownerId = $get('owner_id');
                                $loteId = $get('lote_id');
                                $period = $value;
                                if ($ownerId && $loteId && $period) {
                                    $exists = \App\Models\Invoice::where('owner_id', $ownerId)
                                        ->where('lote_id', $loteId)
                                        ->whereYear('period', \Carbon\Carbon::parse($period)->year)
                                        ->whereMonth('period', \Carbon\Carbon::parse($period)->month)
                                        ->exists();
                                    if ($exists) {
                                        $fail('Ya existe una factura para este propietario, lote y periodo.');
                                    }
                                }
                            };
                        }
                    ]),
                TextInput::make('total')
                    ->numeric()
                    ->readOnly()
                    ->label('Total (suma de ítems)')
                    ->default(0),
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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'pagada' => 'Pagada',
                        'vencida' => 'Vencida',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pendiente' => 'gray',
                        'pagada' => 'success',
                        'vencida' => 'danger',
                        default => 'gray',
                    }),
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
