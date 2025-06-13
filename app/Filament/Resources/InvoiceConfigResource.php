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
use Filament\Notifications\Notification;
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
                            ->columns(4)
                            ->schema([

                                /**
                                 * CANTIDAD DE FACTURAS TODO
                                 * debe registrarse y guardarse en el json. cuantas facturas
                                 */
                                Fieldset::make('Facturas')
                                    ->columns(1)
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_facturas_guardadas')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.875rem;','class'=> 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white'])
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return 0;
                                                $other = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'other_properties');
                                                // Compatibilidad: buscar en ['data']['facturas_count'] y en ['facturas_count']
                                                $facturasCount = 0;
                                                if (isset($other['data']) && is_array($other['data']) && isset($other['data']['facturas_count'])) {
                                                    $facturasCount = $other['data']['facturas_count'];
                                                } elseif (isset($other['facturas_count'])) {
                                                    $facturasCount = $other['facturas_count'];
                                                }
                                                return is_numeric($facturasCount) ? (int)$facturasCount : 0;
                                            })
                                    ]),

                                Fieldset::make('Facturas personalizadas')
                                    ->columnSpan(1)
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_lotes_en_grupos_personalizados')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.875rem;','class'=> 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white'])
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return '0';
                                                $bloque = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'custom_items_invoices');
                                                $grupos = $bloque['data']['groups'] ?? [];
                                                $totalLotesEnGrupos = collect($grupos)->pluck('lotes_id')->flatten()->unique()->count();
                                                return $totalLotesEnGrupos;
                                            }),
                                    ]),
                                Fieldset::make('Facturas excluidos')
                                    ->columnSpan(1)
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_lotes_excluidos')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.875rem;','class'=> 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white'])
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return '0';
                                                $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
                                                $excluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
                                                return is_array($excluidos) ? count($excluidos) : 0;
                                            }),
                                     ]),
                                Fieldset::make('Facturas totales')
                                    ->columnSpan(1)
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_facturas_a_crear')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.875rem;','class'=> 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white'])
                                            ->content(function (Get $get) {
                                                $config = $get('config');
                                                if (!is_array($config)) return 0;
                                                $other = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'other_properties');
                                                // Compatibilidad: buscar en ['data']['facturas_count'] y en ['facturas_count']
                                                $facturasCount = 0;
                                                if (isset($other['data']) && is_array($other['data']) && isset($other['data']['facturas_count'])) {
                                                    $facturasCount = $other['data']['facturas_count'];
                                                } elseif (isset($other['facturas_count'])) {
                                                    $facturasCount = $other['facturas_count'];
                                                }
                                                // Excluidos
                                                $bloqueExcluidos = collect($config)->first(fn($b) => ($b['type'] ?? null) === 'exclude_lotes');
                                                $excluidos = $bloqueExcluidos['data']['lotes_id'] ?? [];
                                                $totalExcluidos = is_array($excluidos) ? count($excluidos) : 0;
                                                // Asegurar que ambos sean numéricos
                                                $facturasCount = is_numeric($facturasCount) ? (int)$facturasCount : 0;
                                                $totalExcluidos = is_numeric($totalExcluidos) ? (int)$totalExcluidos : 0;
                                                return $facturasCount - $totalExcluidos;
                                            }),
                                    ]),

                                Forms\Components\Placeholder::make('resumen_grupos')
                                    ->label('Facturas personalizadas')
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
                                        )))->where('is_facturable', true)->get()->keyBy('id');
                                        $html = '<table style="width:100%" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden text-sm">';
                                        $html .= '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700">Grupo</th>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700 w-40">Cantidad de lotes</th>';
                                        $html .= '<th class="px-4 py-2 text-left font-semibold text-gray-700 dark:text-gray-200 border-b dark:border-gray-700">Lotes</th>';
                                        $html .= '</tr></thead><tbody class="bg-white dark:bg-gray-900">';
                                        foreach ($grupos as $i => $grupo) {
                                            $nombre = $grupo['name'] ?? 'Grupo ';
                                            $lotes = $grupo['lotes_id'] ?? [];
                                            $cantidadLotes = is_array($lotes) ? count($lotes) : 0;
                                            if ($cantidadLotes > 0) {
                                                $nombres = collect($lotes)->map(function($id) use ($allLotes) {
                                                    $nombreLote = $allLotes[$id]->getNombre() ?? $id;
                                                    return "<span class='inline-block rounded-full bg-blue-100 dark:bg-blue-700 text-blue-800 dark:text-blue-100 px-3 py-1 text-xs font-semibold mr-1 mb-1'>{$nombreLote}</span>";
                                                })->implode(' ');
                                                $lotesList = "<div class='flex flex-wrap gap-1'>{$nombres}</div>";
                                            } else {
                                                $lotesList = '<em class="text-gray-400 dark:text-gray-500">Sin lotes</em>';
                                            }
                                            $html .= "<tr><td class='px-4 py-2 border-b dark:border-gray-700'><strong>{$nombre}</strong></td><td class='px-4 py-2 border-b dark:border-gray-700'>{$cantidadLotes}</td><td class='px-4 py-2 border-b dark:border-gray-700'>{$lotesList}</td></tr>";
                                        }
                                        // Fila de excluidos
                                        $cantidadExcluidos = is_array($excluidos) ? count($excluidos) : 0;
                                        if ($cantidadExcluidos > 0) {
                                            $nombresExcluidos = collect($excluidos)->map(function($id) use ($allLotes) {
                                                $nombreLote = $allLotes[$id]->getNombre() ?? $id;
                                                return "<span class='inline-block rounded-full bg-red-100 dark:bg-red-700 text-red-800 dark:text-red-100 px-3 py-1 text-xs font-semibold mr-1 mb-1'>{$nombreLote}</span>";
                                            })->implode(' ');
                                            $html .= "<tr class='bg-red-50 dark:bg-red-900'><td class='px-4 py-2 border-b dark:border-gray-700'><strong>Excluidos</strong></td><td class='px-4 py-2 border-b dark:border-gray-700'>-{$cantidadExcluidos}</td><td class='px-4 py-2 border-b dark:border-gray-700'><div class='flex flex-wrap gap-1'>{$nombresExcluidos}</div></td></tr>";
                                        }
                                        $html .= '</tbody></table>';
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->extraAttributes(['style' => 'font-size:1em'])
                                    ->columnSpanFull(),

                                Fieldset::make('Fechas de control')
                                    ->columnSpan(3)
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\Placeholder::make('fechas_control')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.2rem;'])
                                            ->helperText('La fecha de ejecución es la fecha en la que se generarán las facturas. El periodo de facturación es el mes y año correspondiente a las facturas generadas.')
                                            ->content(function (Get $get) {
                                                $period = $get('period');
                                                $fechaCreacion = $get('fecha_creacion');
                                                $periodStr = $period ? \Carbon\Carbon::parse($period)->translatedFormat('F Y') : '-';
                                                $fechaCreacionStr = $fechaCreacion ? \Carbon\Carbon::parse($fechaCreacion)->format('d/m/Y') : '-';
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div><strong>Periodo de facturación:</strong> ' . $periodStr . '</div>' .
                                                    '<div><strong>Fecha de ejecución:</strong> ' . $fechaCreacionStr . '</div>'
                                                );
                                            }),
                                    ]),
                                Fieldset::make('Estado')
                                    ->label('')
                                    ->columnSpan(1)
                                    ->columns(1)
                                    ->extraAttributes(function (Get $get) {
                                        $status = $get('status');
                                        $base = 'rounded-lg border';
                                        $map = [
                                            'Borrador' => 'background-color: rgb(207, 110, 6); color: white; border-color: rgb(207,110, 6); ',
                                            'Procesado' => 'background-color: rgb(34,197,94); color: white; border-color: rgb(34,197,94); ',
                                            'Aprobado' => 'background-color: rgb(59,130,246); color: white; border-color: rgb(59,130,246); ',
                                        ];
                                        return [
                                            // 'class' => $map[$status] ?? ($base.' '),
                                            'style' => $map[$status] ?? 'background-color: rgb(76, 76, 76); color: white; border-color: rgb(66, 66, 66);',
                                        ];
                                    })
                                    ->schema([
                                        Forms\Components\Placeholder::make('status')
                                            ->label('')
                                            ->extraAttributes(['style' => 'font-size: 1.875rem;','class'=> 'fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white'])
                                            ->content(function (Get $get) {
                                                $status = $get('status');
                                                $status = $status == null ? 'Pendiente' : $status;
                                                return $status;
                                            }),
                                    ]),
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

                                                                    $lotes = Lote::get()->where('is_facturable', true);

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
                                                $query = Lote::query()->whereNotIn('id', $lotesEnGrupos)->where('is_facturable', true);

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
                            ->minItems(2)
                            ->maxItems(4)
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
                ->columnSpanFull()
                ->disabled(fn (Get $get) => $get('status') !== 'Borrador'),
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
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn($record) => $record->status !== 'Aprobado')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = 'Aprobado';
                        $record->aprobe_user_id = auth()->id();
                        $record->aprobe_date = now();
                        $record->save();
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
