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
                    ->required()
                    ->disabled(fn ($context) => $context === 'edit'),
                Select::make('lote_id')
                    ->label('Lote')
                    ->options(function (Get $get) {
                        $ownerId = $get('owner_id');
                        if (!$ownerId) return [];
                        $lotes = Lote::where('owner_id', $ownerId)->get();
                        return $lotes->mapWithKeys(function ($lote) {
                            return [
                                $lote->id => "{$lote->getNombre()}"
                            ];
                        });
                    })
                    ->required()
                    ->disabled(fn ($context) => $context === 'edit' || !$context),
                DatePicker::make('period')
                    ->required()
                    ->displayFormat('F Y')
                    // ->format('Y-m-01')
                    ->disabled(fn ($context) => $context === 'edit')
                    ->rules([
                        function (Get $get, $context) {
                            return function ($attribute, $value, $fail) use ($get, $context) {
                                // Solo validar duplicados al crear
                                if ($context !== 'create') return;
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
                DatePicker::make('due_date')->label('Fecha de vencimiento')
                    ->required()
                    ->minDate(fn (Get $get) => $get('period')),
                TextInput::make('total')
                    ->numeric()
                    ->readOnly()
                    ->label('Total (suma de ítems)')
                    ->default(0)
                    ->dehydrated(true)
                    ->visible(false),
                Select::make('status')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'pagada' => 'Pagada',
                        'vencida' => 'Vencida',
                    ])->required(),
            ])
            ->columns(2)
            ->live();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_identifier')->sortable(),
                TextColumn::make('owner')->label('Propietario')
                    ->formatStateUsing(fn ($record) => $record->owner?->nombres() ?? 'Sin nombres'),
                TextColumn::make('lote')
                    ->formatStateUsing(fn ($record) => $record->lote?->getNombre() ?? 'Sin lote')
                    ->label('Lote'),
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
            ->defaultSort('created_at', 'desc')
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
            RelationManagers\ItemsRelationManager::class,
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
