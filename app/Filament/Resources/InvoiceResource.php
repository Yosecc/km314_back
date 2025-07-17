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
use Filament\Tables\Actions\Action;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

       protected static ?string $navigationGroup = 'Administración contable (vPRUEBA)';
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
                    ->minDate(now()->startOfMonth())
                    ->disabled(fn ($context) => $context === 'edit')
                    ->rules([
                        function (Get $get, $context) {
                            return function ($attribute, $value, $fail) use ($get, $context) {
                                // Validar que el día seleccionado sea 1
                                if ($value && \Carbon\Carbon::parse($value)->day !== 25) {
                                    $fail('Debe seleccionar solo el 25 de cada mes.');
                                }
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
                    // ->required() // Ya no es requerido
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
            ->groups([
                Group::make('owner.first_name')
                     ->getTitleFromRecordUsing(fn (Invoice $record) => "{$record->owner->nombres()}")
                     ->label('Propietario'),
                Group::make('lote.lote_id')
                     ->getTitleFromRecordUsing(fn (Invoice $record) => "{$record->lote?->getNombre()}")
                     ->label('Lote'),
            ])
            ->defaultGroup(
                Group::make('owner.first_name')
                        ->label('Propietario')
                        ->getTitleFromRecordUsing(fn (Invoice $record) => "{$record->owner->nombres()}")
            )
            ->columns([
                TextColumn::make('public_identifier')->sortable(),
                TextColumn::make('owner')->label('Propietario')
                    ->formatStateUsing(fn ($record) => $record->owner?->nombres() ?? 'Sin nombres'),
                TextColumn::make('lote')
                    ->formatStateUsing(fn ($record) => $record->lote?->getNombre() ?? 'Sin lote')
                    ->label('Lote'),
                TextColumn::make('period')->date()->label('Periodo'),
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
            ->defaultSort('period', 'desc')
            ->filters([
                SelectFilter::make('owner_id')
                    ->searchable()
                    ->options(
                        Owner::all()->mapWithKeys(
                            fn ($owner) => [$owner->id => "{$owner->nombres()}"]
                        )
                    )
            ])
            ->actions([
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('factura.pdf', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Action::make('importar-csv')
                    ->label('Importar CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('csv_file')
                            ->label('Archivo CSV')
                            ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                            ->required()
                            ->preserveFilenames()
                            ->disk('local')
                            ->directory('import')
                            ,
                            Select::make('owner_id')
                                ->options(
                                    Owner::all()->mapWithKeys(
                                        fn ($owner) => [$owner->id => "{$owner->nombres()}"]
                                    )
                                )
                                ->label('Propietario')
                                ->live()
                                ->required(),
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
                    ])
                    ->action(function (array $data) {
                        // $data['csv_file'] es la ruta relativa en storage/app/import/
                        $path = storage_path('app/' . $data['csv_file']);
                        try {
                            \Artisan::call('import:accounting-csv', [
                                'file' => $path,
                                'owner_id' => $data['owner_id'],
                                'lote_id' => $data['lote_id'],
                            ]);
                        } catch (\Exception $e) {
                            \Log::info($e->getMessage());
                            \Filament\Notifications\Notification::make()
                                ->title('Error al importar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Importación ejecutada')
                            ->success()
                            ->send();
                    }),
                Action::make('eliminar-importacion')
                    ->label('Eliminar importación (migración)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->form([
                        Select::make('owner_id')
                            ->options(
                                Owner::all()->mapWithKeys(
                                    fn ($owner) => [$owner->id => $owner->nombres()]
                                )
                            )
                            ->searchable()
                            ->label('Propietario')
                            ->live()
                            ->required(),
                        Select::make('lote_id')
                            ->label('Lote')
                            ->options(function (Get $get) {
                                $ownerId = $get('owner_id');
                                if (!$ownerId) return [];
                                $lotes = Lote::where('owner_id', $ownerId)->get();
                                return $lotes->mapWithKeys(function ($lote) {
                                    return [
                                        $lote->id => $lote->getNombre()
                                    ];
                                });
                            })
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data) {
                        $ownerId = $data['owner_id'];
                        $loteId = $data['lote_id'];
                        // Eliminar referencias en account_statuses
                        $payments = \App\Models\Payment::where('owner_id', $ownerId)->get();
                        foreach ($payments as $p) {
                            \DB::table('account_statuses')->where('last_payment_id', $p->id)->update(['last_payment_id' => null]);
                            \DB::table('invoice_payment')->where('payment_id', $p->id)->delete();
                            $p->delete();
                        }
                        // Eliminar items y facturas
                        $facturas = \App\Models\Invoice::where('owner_id', $ownerId)->where('lote_id', $loteId)->get();
                        foreach ($facturas as $factura) {
                            $factura->items()->delete();
                            $factura->delete();
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Importación eliminada')
                            ->success()
                            ->send();
                    }),
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
