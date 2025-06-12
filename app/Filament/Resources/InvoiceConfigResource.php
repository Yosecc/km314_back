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
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as GridInfoList;
use Filament\Infolists\Components\TextEntry;
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

                Wizard::make([
                    Wizard\Step::make('info')
                    ->label('Resumen de la configuración')
                    ->schema([
                        Grid::make()
                            ->columns(1)
                            ->schema([
                                Forms\Components\Placeholder::make('total_lotes_con_owner')
                                    ->label('Total de lotes registrados')
                                    ->content(function () {
                                        return Lote::whereNotNull('owner_id')->count();
                                    }),
                                Forms\Components\Placeholder::make('total_grupos')
                                    ->label('Cantidad de grupos Personalizados')
                                    ->content(function (Get $get) {
                                        $config = $get('config');
                                        if (!is_array($config)) return '0';
                                        $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                        $grupos = $bloque['data']['groups'] ?? [];
                                        return count($grupos);
                                    }),
                                Forms\Components\Placeholder::make('total_lotes_grupos')
                                    ->label('Total de lotes en grupos personalizados')
                                    ->content(function (Get $get) {
                                        $config = $get('config');
                                        if (!is_array($config)) return '0';
                                        $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                        $grupos = $bloque['data']['groups'] ?? [];
                                        // Contar la cantidad de lotes únicos en todos los grupos personalizados
                                        $totalLotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->count();
                                        return $totalLotesEnGrupos;
                                    }),
                                    // En el resumen visual, quitamos los totales de la tabla y los mostramos en campos separados arriba.
                                        Forms\Components\Placeholder::make('total_lotes_en_grupos_personalizados')
                                            ->label('Total de lotes en grupos personalizados')
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return '0';
                                                $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                                $grupos = $bloque['data']['groups'] ?? [];
                                                $totalLotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->count();
                                                return $totalLotesEnGrupos;
                                            }),
                                        Forms\Components\Placeholder::make('total_lotes_excluidos')
                                            ->label('Total de lotes excluidos')
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return '0';
                                                $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
                                                $excluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
                                                return is_array($excluidos) ? count($excluidos) : 0;
                                            }),
                                        Forms\Components\Placeholder::make('total_lotes_generales')
                                            ->label('Total de lotes a los que se aplican ítems generales')
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return '0';
                                                $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                                $grupos = $bloque['data']['groups'] ?? [];
                                                $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
                                                $excluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
                                                $totalLotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->toArray();
                                                $totalLotesConOwner = \App\Models\Lote::whereNotNull('owner_id')->pluck('id')->toArray();
                                                $totalLotesExcluidos = is_array($excluidos) ? $excluidos : [];
                                                $lotesGenerales = array_diff($totalLotesConOwner, $totalLotesEnGrupos, $totalLotesExcluidos);
                                                return count($lotesGenerales);
                                            }),
                                Forms\Components\Placeholder::make('resumen_grupos')
                                    ->label('Grupos y lotes personalizados')
                                    ->content(function (Get $get) {
                                        $config = $get('config');
                                        if (!is_array($config)) return new \Illuminate\Support\HtmlString('Sin grupos');
                                        $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                        $grupos = $bloque['data']['groups'] ?? [];
                                        $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
                                        $excluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
                                        $allLotes = Lote::whereIn('id', array_unique(array_merge(
                                            collect($grupos)->pluck('lotes_id')->flatten()->unique()->toArray(),
                                            is_array($excluidos) ? $excluidos : []
                                        )))->get()->keyBy('id');
                                        $html = '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden text-sm">';
                                        $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700">Grupo</th>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700 w-40">Cantidad de lotes</th>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700">Lotes</th>';
                                        $html .= '</tr></thead><tbody class="bg-white dark:bg-gray-900">';
                                        foreach ($grupos as $i => $grupo) {
                                            $nombre = $grupo['name'] ?? 'Grupo '.($i+1);
                                            $lotes = $grupo['lotes_id'] ?? [];
                                            $cantidadLotes = is_array($lotes) ? count($lotes) : 0;
                                            if ($cantidadLotes > 0) {
                                                $nombres = collect($lotes)->map(function($id) use ($allLotes) {
                                                    return $allLotes[$id]->getNombre() ?? $id;
                                                })->implode(', ');
                                                $lotesList = $nombres;
                                            } else {
                                                $lotesList = '<em class="text-gray-400 dark:text-gray-500">Sin lotes</em>';
                                            }
                                            $html .= "<tr><td class='px-4 py-2 border-b dark:border-gray-700'><strong>{$nombre}</strong></td><td class='px-4 py-2 border-b dark:border-gray-700'>{$cantidadLotes}</td><td class='px-4 py-2 border-b dark:border-gray-700'>{$lotesList}</td></tr>";
                                        }
                                        // Fila de excluidos
                                        $cantidadExcluidos = is_array($excluidos) ? count($excluidos) : 0;
                                        if ($cantidadExcluidos > 0) {
                                            $nombresExcluidos = collect($excluidos)->map(function($id) use ($allLotes) {
                                                return $allLotes[$id]->getNombre() ?? $id;
                                            })->implode(', ');
                                            $html .= "<tr class='bg-red-50 dark:bg-red-900'><td class='px-4 py-2 border-b dark:border-gray-700'><strong>Excluidos</strong></td><td class='px-4 py-2 border-b dark:border-gray-700'>-{$cantidadExcluidos}</td><td class='px-4 py-2 border-b dark:border-gray-700'>{$nombresExcluidos}</td></tr>";
                                        }
                                        $html .= '</tbody></table>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->extraAttributes(['style' => 'font-size:1em'])
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('total_lotes_con_owner')
                                    ->label('Total de lotes con propietario asignado')
                                    ->content(function () {
                                        return Lote::whereNotNull('owner_id')->count();
                                    }),
                            ]),
                    ]),
                    Wizard\Step::make('step_basic_params')
                    ->label('Parámetros Básicos')
                    ->schema([
                        DatePicker::make('period')
                            ->label('Periodo de Facturación')
                            ->required()
                            ->displayFormat('F Y')
                            ->helperText('Selecciona el mes y año de la facturación.'),
                        DatePicker::make('fecha_creacion')
                            ->label('Fecha de Ejecución')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->helperText('Fecha en la que se ejecutará esta configuración y se generarán las facturas.'),
                        DatePicker::make('expiration_date')
                            ->label('Vencimiento Principal')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->helperText('Fecha de vencimiento principal de las facturas generadas.'),
                        DatePicker::make('second_expiration_date')
                            ->label('Segundo Vencimiento')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->helperText('Segunda fecha de vencimiento para pagos fuera de término.'),
                        TextInput::make('punitive')
                            ->label('Interés Moratorio (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required()
                            ->helperText('Porcentaje de interés moratorio aplicado a facturas vencidas.'),
                    ]),
                    Wizard\Step::make('step_config')
                        ->label('Items & Facturas')
                        ->schema([
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
                                                    ->label('Tipo de item')
                                                    // ->helperText('Selecciona si el item es fijo o variable. Los items fijos se toman de los conceptos de gastos predefinidos.')
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

                                                            TextInput::make('name')
                                                                ->label('Nombre del grupo')
                                                                ->helperText('Define un nombre descriptivo para este grupo de lotes.')
                                                                ->live(onBlur: true),

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
                                                                        ->label('Tipo de item')
                                                                        // ->helperText('Selecciona si el item es fijo o variable. Los items fijos se toman de los conceptos de gastos predefinidos.')
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
                                            ->collapsed(fn ($context) => $context === 'edit')
                                            ->collapsible()
                                            ->columns(2)
                                            ->addActionLabel('Agrega grupo de Facturas/lotes')
                                            ->columns(2)
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                                    ])
                                    ->maxItems(1)
                                    ->columns(1),

                                Forms\Components\Builder\Block::make('exclude_lotes')
                                    ->label('Excluir Lotes')
                                    // ->description('Selecciona lotes que no deben ser incluidos en la facturación mensual. Estos lotes no recibirán facturas')
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
                                                // Obtener todos los lotes asignados a grupos personalizados
                                                $config = $get('../../../config');
                                                $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                                $grupos = $bloque['data']['groups'] ?? [];
                                                $lotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->toArray();

                                                // Traer solo los lotes que NO están en ningún grupo
                                                $query = Lote::query()->whereNotIn('id', $lotesEnGrupos);

                                                // Si hay filtro por tipo, aplicarlo
                                                if ($get('lote_type_id')) {
                                                    $query->where('lote_type_id', $get('lote_type_id'));
                                                }

                                                return $query->get()->mapWithKeys(function ($lote) {
                                                    return [
                                                        $lote->id => $lote->getNombre()
                                                    ];
                                                });
                                            })
                                            ->required()
                                            ->helperText('Solo puedes excluir lotes que no estén en ningún grupo personalizado.')

                                    ])
                                    ->maxItems(1)
                                    ->columns(3),


                            ])
                            ->collapsed(fn ($context) => $context === 'edit')
                            ->cloneable()
                            ->minItems(1)
                            ->maxItems(3)
                            ->collapsible()
                            ->addActionLabel('Agregar bloque de configuracion')
                            ->blockNumbers(false)
                            ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation(),
                            )
                            ->columnSpanFull(),
                        ]),

                ])
                ->skippable(fn ($context) => $context === 'edit')
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
