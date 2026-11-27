<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Works;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Employee;
use App\Models\Trabajos;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FilesRequired;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use App\Models\ConstructionCompanie;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\EmployeeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use App\Filament\Resources\EmployeeResource\Traits\HasGestionAction;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;



class EmployeeResource extends Resource implements HasShieldPermissions
{
    use HasNotesAction, HasGestionAction;

    private const OPTIONAL_DOCUMENTS_PERMISSION = 'make_documents_optional_employee';

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Gestión de Trabajadores';
    protected static ?string $label = 'trabajador';
    // protected static ?string $navigationGroup = 'Web';

    public static function canViewAny(): bool
    {
        // Si el usuario no ha aceptado los términos, no puede ver el recurso
        if(Auth::user()->hasRole('owner')){
            $user = auth()->user();
            return $user && $user->is_terms_condition;
        }

        if(Auth::user()->hasRole(['super_admin','admin'])){
            return true;
        }

        return auth()->user()->can('view_any_employee');
    }

    public static function getPluralModelLabel(): string
    {
        return 'trabajadores';
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'make_documents_optional',
        ];
    }

    private static function documentsAreOptionalForCurrentUser(): bool
    {
        return Auth::user()?->can(self::OPTIONAL_DOCUMENTS_PERMISSION) ?? false;
    }

    private static function formDatosPersonales()
    {
        return [
            Forms\Components\Hidden::make('status')
                ->default(function(){
                    if (Auth::user()->hasRole('owner')) {
                        return 'pendiente';
                    }
                    return 'aprobado'; // Para admin u otros roles
                }),
            Forms\Components\Select::make('work_id')
                ->label(__("general.Work"))
                ->required()
                ->validationAttribute('obra')
                ->validationMessages([
                    'required' => 'Seleccione la obra del trabajador.',
                ])
                ->default(37)
                ->visible(function(){
                    if (Auth::user()->hasRole('super_admin')) {
                        return true;
                    }
                    return false;
                })
                ->relationship(name: 'work', titleAttribute: 'name'),

            Forms\Components\TextInput::make('dni')
                ->label(__("general.DNI"))
                ->required()
                ->numeric()
                ->rules(['digits_between:6,12'])
                ->validationAttribute('DNI')
                ->validationMessages([
                    'required' => 'Ingrese el DNI del trabajador.',
                    'numeric' => 'El DNI solo puede contener números.',
                    'digits_between' => 'El DNI debe contener entre 6 y 12 dígitos.',
                ]),

            Forms\Components\TextInput::make('first_name')
                ->label(__("general.FirstName"))
                ->required()
                ->maxLength(255)
                ->validationAttribute('nombre')
                ->validationMessages([
                    'required' => 'Ingrese el nombre del trabajador.',
                    'max' => 'El nombre no puede superar los 255 caracteres.',
                ]),

            Forms\Components\TextInput::make('last_name')
                ->label(__("general.LastName"))
                ->required()
                ->maxLength(255)
                ->validationAttribute('apellido')
                ->validationMessages([
                    'required' => 'Ingrese el apellido del trabajador.',
                    'max' => 'El apellido no puede superar los 255 caracteres.',
                ]),

            Forms\Components\TextInput::make('phone')
                ->label(__("general.Phone"))
                ->tel()
                ->required()
                ->numeric()
                ->validationAttribute('teléfono')
                ->validationMessages([
                    'required' => 'Ingrese el teléfono del trabajador.',
                    'numeric' => 'El teléfono solo puede contener números.',
                ]),

            Forms\Components\Hidden::make('user_id')->disabled(fn($context)=> $context == 'edit')->default(Auth::user()->id),

            Repeater::make('employeeOrigens')
                ->label('Origen adicional del trabajador')
                // ->helperText('Solo para Compañías de Construcción o KM314. Los propietarios se gestionan más abajo.')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('model')
                        ->label('Tipo de origen')
                        ->options([
                            'ConstructionCompanie' => 'Compañía de Construcción',
                            'Employee' => 'KM314',
                        ])
                        ->required()
                        ->validationAttribute('tipo de origen')
                        ->validationMessages([
                            'required' => 'Seleccione el tipo de origen.',
                        ])
                        ->live()
                        ->afterStateUpdated(function(Set $set){
                            $set('model_id', null);
                        }),

                    Forms\Components\Select::make('model_id')
                        ->label(function(Get $get){
                            $model = $get('model');
                            return match($model) {
                                'ConstructionCompanie' => 'Seleccione la compañía',
                                'Employee' => 'KM314',
                                default => 'Seleccione el origen'
                            };
                        })
                        ->options(function(Get $get){
                            $model = $get('model');
                            return match($model) {
                                'ConstructionCompanie' => ConstructionCompanie::get()->pluck('name', 'id')->toArray(),
                                default => []
                            };
                        })
                        ->searchable()
                        ->required(function(Get $get){
                            $model = $get('model');
                            return $model === 'ConstructionCompanie';
                        })
                        ->visible(function(Get $get){
                            $model = $get('model');
                            return $model === 'ConstructionCompanie';
                        })
                        ->validationAttribute('compañía de origen')
                        ->validationMessages([
                            'required' => 'Seleccione la compañía de origen.',
                        ])
                        ->live(),
                ])
                ->addActionLabel('Agregar origen')
                ->columns(1)
                ->collapsible()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string =>
                    isset($state['model'])
                        ? ($state['model'] === 'ConstructionCompanie' && isset($state['model_id'])
                            ? 'Compañía: ' . (ConstructionCompanie::find($state['model_id'])->name ?? 'N/A')
                            : ($state['model'] === 'Employee' ? 'KM314' : 'Origen sin configurar'))
                        : 'Origen sin configurar'
                )
                ->visible(function(){
                    if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                        return false;
                    }
                    return true;
                })
                ,

            // DatePicker::make('fecha_vencimiento_seguro')
            //     ->label('Fecha de vencimiento del seguro personal')
            //     ->displayFormat('d/m/Y')
            //     ->required()
            //     ->default(Carbon::now()->addMonths(config('employees.documentation_renewal_months')))
            //     ->hidden(true)
            //     ->dehydrated()
            //     ->live()
            //     ,
            // Forms\Components\Select::make('owner_id')
            //     ->label('Propietario')
            //     ->searchable()
            //     ->options(function(){
            //         return Owner::get()->map(function($owner){
            //             $owner['texto'] = $owner->nombres();
            //             return $owner;
            //         })->pluck('texto','id')->toArray();
            //     })
            //     ->default(function(){
            //         if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            //             return Auth::user()->owner_id;
            //         }
            //     })
            //     ->visible(function(){
            //         if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            //             return false;
            //         }
            //         return true;
            //     }),
        // Cambiar el select de owner_id por un select múltiple
        Forms\Components\Select::make('owners')
            ->label('Propietarios')
            ->multiple()
            ->searchable()
            ->relationship('owners', 'first_name')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombres())
            ->preload()
            ->default(function($record){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return [Auth::user()->owner_id];
                }
                // Si es edición y tiene owner_id, incluirlo por defecto
                return $record && $record->owner_id ? [$record->owner_id] : [];
            })
            ->visible(function(){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return false;
                }
                return true;
            }),

        // Mantener temporalmente el campo owner_id oculto para compatibilidad
        Forms\Components\Hidden::make('owner_id')
            ->default(function(){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return Auth::user()->owner_id;
                }
            }),

            Forms\Components\TextInput::make('observations')
                ->label('Observaciones del trabajo a realizar')
                ->columnSpanFull(),

            Repeater::make('horarios')
                ->label('Horarios de trabajo')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('day_of_week')
                        ->label('Día')
                        ->options([
                            'Domingo' => 'Domingo',
                            'Lunes' => 'Lunes',
                            'Martes' => 'Martes',
                            'Miercoles' => 'Miercoles',
                            'Jueves' => 'Jueves',
                            'Viernes' => 'Viernes',
                            'Sabado' => 'Sabado',
                        ])
                        ->required(),
                    Forms\Components\TimePicker::make('start_time')
                        ->label('Hora de entrada')
                        ->seconds(false)
                        ->required(),
                    Forms\Components\TimePicker::make('end_time')
                        ->label('Hora de salida')
                        ->seconds(false)
                        ->required(),
                ])
                ->addActionLabel('Agregar horario')
                ->defaultItems(0)
                ->columns(3)
                ->columnSpanFull()
                ->itemLabel(fn (array $state): ?string =>
                    isset($state['day_of_week'])
                        ? $state['day_of_week'] . (isset($state['start_time']) && isset($state['end_time']) ? ' (' . $state['start_time'] . ' - ' . $state['end_time'] . ')' : '')
                        : null
                )
                ->collapsible(),
        ];
    }

    private static function getArchivos($type)
    {
        $filesRequired = FilesRequired::where('type', $type)->first();
        if (!$filesRequired) {
            return [];
        }

        $archivos = collect($filesRequired->required)->map(function($item){
            return [
                'name' => $item['document'],
                'is_required_fecha_vencimiento' => $item['date_is_required'],
                'is_required' => $item['is_required'],
            ];
        });

        return $archivos->toArray();
    }

    private static function orderConfiguredFiles(array $state, array $configured): array
    {
        $configuredByName = collect($configured)->keyBy('name');
        $configuredOrder = $configuredByName->keys()->flip();

        foreach ($state as $key => $file) {
            $configuration = $configuredByName->get($file['name'] ?? null);

            if ($configuration) {
                $state[$key] = array_merge($file, $configuration, [
                    'is_historical' => false,
                ]);

                continue;
            }

            $state[$key] = array_merge($file, [
                'is_required' => false,
                'is_required_fecha_vencimiento' => false,
                'is_historical' => true,
            ]);
        }

        $existingNames = collect($state)->pluck('name')->filter();

        foreach ($configured as $file) {
            if (! $existingNames->contains($file['name'] ?? null)) {
                $state[] = array_merge($file, [
                    'is_historical' => false,
                ]);
            }
        }

        uasort($state, function (array $first, array $second) use ($configuredOrder): int {
            $fallbackPosition = $configuredOrder->count();
            $firstPosition = $configuredOrder->get($first['name'] ?? '', $fallbackPosition);
            $secondPosition = $configuredOrder->get($second['name'] ?? '', $fallbackPosition);

            return $firstPosition <=> $secondPosition;
        });

        return $state;
    }

    private static function prepareFileForCreate(array $data): ?array
    {
        unset(
            $data['is_required'],
            $data['is_required_fecha_vencimiento'],
            $data['is_historical'],
        );

        return filled($data['file'] ?? null) ? $data : null;
    }

    private static function prepareFileForSave(array $data): ?array
    {
        $isHistorical = (bool) ($data['is_historical'] ?? false);

        unset(
            $data['is_required'],
            $data['is_required_fecha_vencimiento'],
            $data['is_historical'],
        );

        return $isHistorical ? null : $data;
    }

    private static function configureHistoricalFileDeleteAction(Action $action): Action
    {
        return $action
            ->requiresConfirmation()
            ->modalHeading('Eliminar documento histórico')
            ->modalDescription('El documento se eliminará definitivamente al guardar los cambios del trabajador.')
            ->visible(function (array $arguments, Repeater $component): bool {
                $state = $component->getRawItemState($arguments['item']);

                return (bool) ($state['is_historical'] ?? false);
            });
    }

    private static function formArchivosPersonales()
    {



        return [
            Repeater::make('files')
                    ->relationship()
                    ->label('Documentos')
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): ?array {
                        return self::prepareFileForCreate($data);
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): ?array {
                        return self::prepareFileForSave($data);
                    })
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data) {
                        return $data;
                    })
                    ->afterStateHydrated(function ($component, $state) {
                        $component->state(self::orderConfiguredFiles(
                            $state ?? [],
                            self::getArchivos('employee'),
                        ));
                    })
                    ->schema([
                        // TextEntry::make('name'),
                        Forms\Components\Hidden::make('name')->dehydrated(),
                        Forms\Components\Hidden::make('is_required')->dehydrated(false),
                        Forms\Components\Hidden::make('is_required_fecha_vencimiento')->dehydrated(false),
                        Forms\Components\Hidden::make('is_historical'),
                        DatePicker::make('fecha_vencimiento')
                            ->label('Fecha de vencimiento del documento (dd-mm-aaaa)')
                            ->validationAttribute(fn (Get $get): string => 'fecha de vencimiento de '.($get('name') ?: 'documento'))
                            ->validationMessages([
                                'required' => 'Ingrese la :attribute.',
                                'date' => 'La :attribute no es una fecha válida.',
                            ])
                            ->extraAttributes(function(Get $get, $state){
                                        if($state  && Carbon::parse($state)->isPast()){
                                            return ['style' => 'border-color: crimson;border-width: 1px;border-radius: 8px;padding: 10px;'];
                                        }
                                        return [];
                                    })
                            ->hidden(function(Get $get, Set $set, $context){
                                if($context == 'edit' || $context == 'view'){
                                    return false;
                                }
                                $is_required = $context == 'create' && $get('is_required_fecha_vencimiento') ?? false;
                                return !$is_required;
                            })
                            ->required(function(Get $get, Set $set, $context){
                                if ($get('is_historical') || self::documentsAreOptionalForCurrentUser()) {
                                    return false;
                                }

                                $is_required = $get('is_required_fecha_vencimiento') ?? false;
                                return $is_required;
                            })
                            ->disabled(fn (Get $get): bool => (bool) $get('is_historical')),
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo')
                            ->validationAttribute(fn (Get $get): string => 'archivo de '.($get('name') ?: 'documento'))
                            ->validationMessages([
                                'required' => 'Debe cargar el :attribute.',
                                'uploaded' => 'No se pudo subir el :attribute. Intente nuevamente.',
                                'max' => 'El :attribute no puede superar los 10 MB.',
                                'mimetypes' => 'El :attribute debe ser una imagen JPG, PNG o WEBP, o un archivo PDF.',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(10240)
                            ->required(function(Get $get, Set $set, $context){
                                if ($get('is_historical') || self::documentsAreOptionalForCurrentUser()) {
                                    return false;
                                }

                                $is_required = $get('is_required') ?? false;
                                return $is_required;
                            })
                            ->storeFileNamesIn('attachment_file_names')
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                return $file ? $file->getClientOriginalName() : $record->file;
                            })
                            ->disabled(fn (Get $get): bool => (bool) $get('is_historical'))
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    // ->maxItems(5)
                    ->addable(false)
                    ->deletable()
                    ->deleteAction(fn (Action $action): Action => self::configureHistoricalFileDeleteAction($action))
                    ->itemLabel(fn (array $state): ?string => isset($state['name'])
                        ? $state['name'] . (($state['is_historical'] ?? false) ? ' (Histórico)' : '')
                        : null)
                    ->default(self::getArchivos('employee'))
                    ->grid(2)
                    ->columns(1)
        ];
    }

    private static function camposAutosFiles()
    {
        return [
            Forms\Components\Hidden::make('name')->dehydrated(),
            Forms\Components\Hidden::make('is_required')->dehydrated(false),
            Forms\Components\Hidden::make('is_required_fecha_vencimiento')->dehydrated(false),
            Forms\Components\Hidden::make('is_historical'),
            DatePicker::make('fecha_vencimiento')
                ->label('Fecha de vencimiento del documento (dd-mm-aaaa)')
                ->validationAttribute(fn (Get $get): string => 'fecha de vencimiento de '.($get('name') ?: 'documento del vehículo'))
                ->validationMessages([
                    'required' => 'Ingrese la :attribute.',
                    'date' => 'La :attribute no es una fecha válida.',
                ])
                ->extraAttributes(function(Get $get, $state){
                    if(Carbon::parse($state)->isPast()){
                        return ['style' => 'border-color: crimson;border-width: 1px;border-radius: 8px;padding: 10px;'];
                    }
                    return [];
                })
                ->required(function(Get $get, Set $set, $context){
                                if ($get('is_historical') || self::documentsAreOptionalForCurrentUser()) {
                                    return false;
                                }

                                $is_required = $get('is_required_fecha_vencimiento') ?? false;
                                return $is_required;
                            })
                ->disabled(fn (Get $get): bool => (bool) $get('is_historical')),
            Forms\Components\FileUpload::make('file')
                ->label('Archivo')
                ->validationAttribute(fn (Get $get): string => 'archivo de '.($get('name') ?: 'documento del vehículo'))
                ->validationMessages([
                    'required' => 'Debe cargar el :attribute.',
                    'uploaded' => 'No se pudo subir el :attribute. Intente nuevamente.',
                    'max' => 'El :attribute no puede superar los 10 MB.',
                    'mimetypes' => 'El :attribute debe ser una imagen JPG, PNG o WEBP, o un archivo PDF.',
                ])
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->maxSize(10240)
                ->required(function(Get $get, Set $set, $context){
                                if ($get('is_historical') || self::documentsAreOptionalForCurrentUser()) {
                                    return false;
                                }

                                $is_required = $get('is_required') ?? false;
                                return $is_required;
                            })
                ->storeFileNamesIn('attachment_file_names')
                ->openable()
                ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                    return $file ? $file->getClientOriginalName() : $record->file;
                })
                ->disabled(fn (Get $get): bool => (bool) $get('is_historical'))
                ->dehydrated(fn ($state) => filled($state))
        ];
    }

    private static function camposAutos()
    {
        return [
            Forms\Components\TextInput::make('marca')
                ->label(__("general.Marca"))
                ->maxLength(255),
            Forms\Components\TextInput::make('modelo')
                ->label(__("general.Modelo"))
                ->maxLength(255),
            Forms\Components\TextInput::make('patente')
                ->label(__("general.Patente"))
                ->maxLength(255),
            Forms\Components\TextInput::make('color')
                ->label(__("general.Color"))
                ->maxLength(255),
            Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                // ->maxLength(255),
            Forms\Components\Hidden::make('model')
                ->default('Employee'),
                // ->maxLength(255),
            Repeater::make('files')
                ->relationship()
                ->label('Documentos del vehículo')
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): ?array {
                    return self::prepareFileForCreate($data);
                })
                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): ?array {
                    return self::prepareFileForSave($data);
                })
                ->schema(self::camposAutosFiles())
                ->afterStateHydrated(function (Repeater $component, $state) {
                    $component->state(self::orderConfiguredFiles(
                        $state ?? [],
                        self::vehicleDocumentDefaults(),
                    ));
                })
                ->defaultItems(3)
                ->minItems(3)
                ->maxItems(3)
                ->addable(false)
                ->deletable()
                ->deleteAction(fn (Action $action): Action => self::configureHistoricalFileDeleteAction($action))
                ->grid(2)
                ->itemLabel(fn (array $state): ?string => isset($state['name'])
                    ? $state['name'] . (($state['is_historical'] ?? false) ? ' (Histórico)' : '')
                    : null)
                ->default(self::getArchivos('car'))
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    private static function vehicleDocumentDefaults(): array
    {
        $configured = self::getArchivos('car');

        return !empty($configured) ? $configured : [
            ['name' => 'Seguro del Vehículo'],
            ['name' => 'VTV'],
            ['name' => 'Cédula del Vehículo'],
        ];
    }

    private static function formAutos()
    {
        return [
            Forms\Components\Repeater::make('autos')
                    ->label('Vehículos')
                    ->relationship()
                    ->mutateRelationshipDataBeforeFillUsing(function ($record, $data) {
                        $data['model'] = $record->autos->where('id', $data['id'])->first()->model;
                        return $data;
                    })
                    ->schema([
                         Forms\Components\TextInput::make('marca')
                            ->label(__("general.Marca"))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('modelo')
                            ->label(__("general.Modelo"))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('patente')
                            ->label(__("general.Patente"))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('color')
                            ->label(__("general.Color"))
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                            // ->maxLength(255),
                        Forms\Components\Hidden::make('model')
                            ->default('Employee'),
                            // ->maxLength(255),
                        Repeater::make('files')
                            ->relationship()
                            ->label('Documentos del vehículo')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): ?array {
                                return self::prepareFileForCreate($data);
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): ?array {
                                return self::prepareFileForSave($data);
                            })
                            ->schema(self::camposAutosFiles())
                            ->afterStateHydrated(function (Repeater $component, $state) {
                                $component->state(self::orderConfiguredFiles(
                                    $state ?? [],
                                    self::vehicleDocumentDefaults(),
                                ));
                            })
                            ->defaultItems(3)
                            ->minItems(3)
                            ->maxItems(3)
                            ->addable(false)
                            ->deletable()
                            ->deleteAction(fn (Action $action): Action => self::configureHistoricalFileDeleteAction($action))
                            ->grid(2)
                            ->itemLabel(fn (array $state): ?string => isset($state['name'])
                                ? $state['name'] . (($state['is_historical'] ?? false) ? ' (Histórico)' : '')
                                : null)
                            ->default(self::vehicleDocumentDefaults())
                            ->columns(1)
                            ->columnSpanFull(),
                    ])
                    ->itemLabel('Información del vehículo')
                    ->addActionLabel('Agregar vehículo')
                    ->defaultItems(0)
                    ->columns(2)
        ];
    }

    private static function formHorarios()
    {
        return [
            Forms\Components\Repeater::make('horarios')
                    ->relationship()
                    ->schema([
                        // Placeholder::make('')->content('Selecciona el dia y el horario de trabajo')->columnSpanFull(),
                        Forms\Components\Select::make('day_of_week')
                            ->label(__("Día"))
                            ->options([
                                'Domingo' => 'Domingo', 'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miercoles', 'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sabado'
                            ])
                            ->required(),
                        Forms\Components\Hidden::make('start_time')
                            ->label(__("Hora de entrada"))
                            ->required()
                            ->default('00:00'),
                        Forms\Components\Hidden::make('end_time')
                            ->label(__("Hora de salida"))
                            ->default('23:59')
                            ->required(),
                    ])
                    ->itemLabel('Selecciona el día de trabajo')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->grid(2)
                    ->columns(1),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                        Wizard\Step::make('Información')
                            ->icon('heroicon-m-information-circle')
                            ->schema(self::formDatosPersonales())
                            ->columns(2),
                        Wizard\Step::make('Documentos personales')
                            ->icon('heroicon-m-document-text')
                            ->schema(self::formArchivosPersonales()),
                        Wizard\Step::make('Vehiculos')
                            ->icon('heroicon-m-truck')
                            ->schema(self::formAutos()),
                        // Wizard\Step::make('Días de trabajo')
                        //     ->icon('heroicon-m-calendar')
                        //     ->schema(self::formHorarios()),
                    ])
                    ->skippable(function ($context) {
                        return $context == 'edit' || $context == 'view';
                    }),
            ])->columns(1);
    }

    public static function isVencimientos($record)
    {
        $color = '';
        $texto = '';
        $status = false;

        if($record->isReverificacion()){
            $color = "warning";
            $texto = "Trabajador pendiente de reverificación de datos.";
            $status = true;
        }

        $vencidosFile = $record->vencidosFile();
        if($vencidosFile){
            $color = "danger";
            $texto = "Documentos  vencidos: ". implode($vencidosFile);
            $status = true;
        }

        $vencidosAutosFile = $record->vencidosAutosFile();
        if($vencidosAutosFile){
            $color = "danger";
            $texto = "Documentos de autos vencidos: ". implode($vencidosAutosFile);
            $status = true;
        }

        return [
            'color' => $color,
            'texto' => $texto,
            'isVencido' => $status,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    $query->where(function($q) {
                        $q->whereHas('owners', function($ownerQuery) {
                            $ownerQuery->where('owner_id', Auth::user()->owner_id);
                        })->orWhere('owner_id', Auth::user()->owner_id);
                    });
                }
                return $query->orderBy('created_at', 'desc');
            })
            ->columns([
                // Tables\Columns\TextColumn::make('work.name')
                //     ->label(__("general.Work"))
                //     ->numeric()
                //     ->sortable()
                //     ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                //     ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                //     ,
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__("general.Phone"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label(__('general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'rechazado' => 'El trabajador ha sido rechazado.',
                        'pendiente' => 'El trabajador está pendiente de aprobación.',
                        'aprobado' => 'El trabajador ha sido aprobado.',
                        default => 'Estado desconocido.',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'rechazado' => 'heroicon-o-x-circle',
                        'pendiente' => 'heroicon-o-clock',
                        'aprobado' => 'heroicon-o-check-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'aprobado' => 'success',
                        'rechazado' => 'danger',
                        default => 'gray',
                    })
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        // Si es owner y el empleado está aprobado, ocultar el botón de editar
                        if (Auth::user()->hasRole('owner') && $record->status === 'aprobado') {
                            return false;
                        }
                        return true;
                    }),
                // Botón de notificaciones en la tabla
                self::getNotesTableAction(),

                // Tables\Actions\Action::make('show_qr')
                //     ->label('Ver QR')
                //     ->icon('heroicon-o-qr-code')
                //     ->color('info')
                //     ->modalHeading('Código de Acceso Rápido')
                //     ->modalDescription(fn ($record) => $record->first_name . ' ' . $record->last_name)
                //     ->modalContent(fn ($record) => view('components.qr-modal', [
                //         'record' => $record,
                //         'qrCode' => $record->generateQrCode(),
                //         'entityType' => 'Empleado'
                //     ]))
                //     ->modalSubmitAction(false)
                //     ->modalCancelActionLabel('Cerrar')
                //     ->visible(function ($record) {
                //         return Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin');
                //     }),

                Tables\Actions\Action::make('verificar_seguro')
                    ->label('Verificar trabajador')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->action(function (Employee $record): void {
                       
                        $record->fecha_vencimiento_seguro = Carbon::now()
                            ->addMonths(config('employees.documentation_renewal_months'));
                        $record->save();

                        Notification::make()
                            ->title('Trabajador verificado')
                            ->body('La fecha de reverificación se ha actualizado correctamente.')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        return Auth::user()->hasRole('super_admin') && $record->isReverificacion();
                    }),

                self::getRenovarDocumentosTableAction(),

                ActionGroup::make([
                    self::getGestionarAutosTableAction(),
                    // self::getGestionarHorariosTableAction(),
                ])->color('info'),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployeeResource::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
