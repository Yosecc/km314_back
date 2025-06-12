<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceConfigResource\Pages;
use App\Filament\Resources\InvoiceConfigResource\RelationManagers;
use App\Models\InvoiceConfig;
use App\Models\Lote;
use App\Models\loteType;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceConfigResource extends Resource
{
    protected static ?string $model = InvoiceConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Administración contable';
    protected static ?string $label = 'Facturación Mensual';
    protected static ?string $pluralLabel = 'Facturaciónes Mensuales';

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
                    ->helperText('Fecha en la que se ejecutará esta configuración de facturación y se generarán las facturas correspondientes.')
                    ->required(),
                DatePicker::make('expiration_date')
                    ->label('Fecha de vencimiento')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->helperText('Fecha de vencimiento de las facturas generadas.'),
                DatePicker::make('second_expiration_date')
                    ->label('Segunda fecha de vencimiento')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->helperText('Segunda fecha de vencimiento de las facturas generadas.'),
                TextInput::make('punitive')
                    ->label('Interés moratorio')
                    ->helperText('Porcentaje de interés moratorio aplicado a las facturas vencidas.')
                    ->numeric()
                    ->required(),
                Forms\Components\Builder::make('config')
                    ->label('Configuración de Facturación')
                    ->blocks([


                        Forms\Components\Builder\Block::make('items_invoice')
                            ->label('Items de Factura')
                            ->schema([

                                Repeater::make(name: 'items')
                                    ->label('Configura los items que se incluirán en la factura mensual. Puedes definir items fijos o variables.')
                                    ->schema([
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
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state) {
                                                    $concept = \App\Models\ExpenseConcept::find($state);
                                                    if ($concept) {
                                                        $set('description', $concept->name);
                                                    }
                                                }
                                            }),
                                        TextInput::make('description')
                                            ->label('Descripción')
                                            ->live()
                                            ->required(fn ($get) => $get('is_fixed') != 1),
                                        TextInput::make('amount')->numeric()->required(),
                                    ])
                                    ->addActionLabel('Agrega Item de factura')
                                    ->columns(4),

                            ])
                            ->maxItems(1)
                            ->columns(1),

                        Forms\Components\Builder\Block::make('custom_items_invoices')
                            ->label('Grupos de Facturas')
                            ->schema([

                                Repeater::make(name: 'groups')
                                    ->label('Personaliza los items de facturación a grupos propietarios/lotes.')
                                    ->schema([
                                        Grid::make('grupo_grid')->schema([
                                            Fieldset::make('lotes')
                                                ->label('Selecciona los lotes para este grupo')
                                                ->schema([

                                                    Select::make('lote_type_id')
                                                        ->label(__("general.LoteType"))
                                                        ->live()
                                                        ->options(function () {
                                                            $lotes = loteType::get();
                                                            return $lotes->mapWithKeys(function ($lote) {
                                                                return [
                                                                    $lote->id => "{$lote->name}"
                                                                ];
                                                            });
                                                        }),
                                                    Select::make('lotes_id')
                                                        ->label('Lote')
                                                        ->multiple()
                                                        ->live()
                                                        ->options(function (Get $get) {

                                                            $lotes = Lote::get();

                                                            if($get('lote_type_id')) {
                                                                $lotes = $lotes->where('lote_type_id', $get('lote_type_id'));
                                                            }
                                                            return $lotes->mapWithKeys(function ($lote) {
                                                                return [
                                                                    $lote->id => "{$lote->getNombre()}"
                                                                ];
                                                            });
                                                        })
                                                        ->required(),
                                                    RichEditor::make(name: 'observations')
                                                        ->label('Observaciones')
                                                        ->helperText('Agrega observaciones o notas personalizadas para este grupo. Estas aparecerán en la factura mensual correspondiente.')
                                                        ->live()
                                                        ->columnSpanFull(),
                                                ])
                                                ->columns(1)
                                                ->columnSpan(1),
                                            Fieldset::make('items_invoice')
                                                ->label('Items de Factura')
                                                ->schema([

                                                    Repeater::make(name: 'items')
                                                        ->label('Configura los items que se incluirán en la factura mensual. Puedes definir items fijos o variables.')
                                                        ->schema([
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
                                                                ->live()
                                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                    if ($state) {
                                                                        $concept = \App\Models\ExpenseConcept::find($state);
                                                                        if ($concept) {
                                                                            $set('description', $concept->name);
                                                                        }
                                                                    }
                                                                }),
                                                            TextInput::make('description')
                                                                ->label('Descripción')
                                                                ->live()
                                                                ->required(fn ($get) => $get('is_fixed') != 1),
                                                            TextInput::make('amount')->numeric()->required(),
                                                        ])
                                                        ->addActionLabel('Agrega Item de factura')
                                                        ->columns(2)

                                                ])
                                                ->columns(1)
                                                ->columnSpan(2),
                                        ])->columns(3),
                                    ])
                                    ->afterStateHydrated(function ($state, Set $set, Get $get) {
                                        // Si el array de grupos está vacío o solo tiene grupos vacíos, copiamos los ítems globales a cada grupo
                                        $global = collect($get('../../../config'))->first(fn($b) => ($b['type'] ?? null) === 'items_invoice');
                                        if ($global && isset($global['data']['items']) && is_array($global['data']['items'])) {
                                            $itemsGlobales = $global['data']['items'];
                                            $grupos = is_array($state) ? $state : [];
                                            $cambio = false;
                                            $nuevosGrupos = collect($grupos)->map(function ($grupo) use ($itemsGlobales, &$cambio) {
                                                $items = $grupo['items'] ?? [];
                                                $itemsVacios = empty($items) || collect($items)->every(fn($item) => collect($item)->filter()->isEmpty());
                                                if ($itemsVacios) {
                                                    $grupo['items'] = $itemsGlobales;
                                                    $cambio = true;
                                                }
                                                return $grupo;
                                            })->toArray();
                                            if ($cambio) {
                                                $set('groups', $nuevosGrupos);
                                            }
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $global = collect($get('../../../config'))->first(fn($b) => ($b['type'] ?? null) === 'items_invoice');
                                        if ($global && isset($global['data']['items']) && is_array($global['data']['items'])) {
                                            $itemsGlobales = $global['data']['items'];
                                            $grupos = is_array($state) ? $state : [];
                                            $cambio = false;
                                            $nuevosGrupos = collect($grupos)->map(function ($grupo) use ($itemsGlobales, &$cambio) {
                                                $items = $grupo['items'] ?? [];
                                                $itemsVacios = empty($items) || collect($items)->every(fn($item) => collect($item)->filter()->isEmpty());
                                                if ($itemsVacios) {
                                                    $grupo['items'] = $itemsGlobales;
                                                    $cambio = true;
                                                }
                                                return $grupo;
                                            })->toArray();
                                            if ($cambio) {
                                                $set('groups', $nuevosGrupos);
                                            }
                                        }
                                    })
                                    ->columns(2)
                                    ->addActionLabel('Agrega grupo de Facturas/lotes')
                                    ->columns(2),
                            ])
                            ->maxItems(1)
                            ->columns(1),

                        Forms\Components\Builder\Block::make('params_general_invoices')
                            ->label('Parametros generales de facturación')
                            ->schema([

                            ])
                            ->maxItems(1)
                            ->columns(3),


                    ])
                    ->minItems(1)
                    ->maxItems(3)
                    ->collapsible()
                    ->addActionLabel('Agregar bloque de configuracion')
                    ->blockNumbers(false)
                    ->deleteAction(
                        fn (Action $action) => $action->requiresConfirmation(),
                    )
                    ->columnSpanFull(),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')->label('Periodo')->date('F Y'),
                Tables\Columns\TextColumn::make('fecha_creacion')->label('Fecha de ejecución')->date(),
                Tables\Columns\TextColumn::make('config')
                    ->label('Resumen configuración')
                    ->formatStateUsing(function($state) {
                        if (!is_array($state)) return 'Sin configuración';
                        $bloques = collect($state);
                        $resumen = [];
                        foreach ($bloques as $bloque) {
                            if (($bloque['type'] ?? null) === 'items_invoice') {
                                $resumen[] = 'Global: '.(isset($bloque['data']['items']) ? count($bloque['data']['items']) : 0).' ítems';
                            } elseif (($bloque['type'] ?? null) === 'custom_items_invoices') {
                                $grupos = $bloque['data']['groups'] ?? [];
                                $resumen[] = 'Grupos: '.count($grupos);
                            }
                        }
                        return implode(' | ', $resumen);
                    }),
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
