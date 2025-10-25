<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormControlResource\Pages;
use App\Filament\Resources\FormControlResource\RelationManagers;
use App\Models\ConstructionCompanie;
use App\Models\FormControl;
use App\Models\FormControlTypeIncome;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\Trabajos;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class FormControlResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = FormControl::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Formulario de control';
    protected static ?string $label = 'formulario';
    protected static ?string $navigationGroup = 'Control de acceso';

    public static function getPluralModelLabel(): string
    {
        return 'formularios';
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'aprobar',
            'rechazar'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Fieldset::make('')
                    ->schema([

                        Forms\Components\CheckboxList::make('access_type')
                            ->label(__("general.TypeActivitie"))
                            ->options((Auth::user()->hasRole('owner') && Auth::user()->owner_id) ? [
                                'lote' => 'Lote',
                            ] : [
                                'general' => 'Entrada general',
                                'playa' => 'Clud playa', 'hause' =>
                                'Club hause',
                                'lote' => 'Lote',
                            ])
                            ->live()
                            ->columns(2)
                            ->required()
                            ->gridDirection('row')
                            ->default(function(){
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return ['lote'];
                                }
                                return [];
                            })
                            ->disabled(function(){
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return true;
                                }
                                return false;
                            })
                            ->dehydrated(function(){
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return true;
                                }
                                return true;
                            }),

                        Forms\Components\Select::make('lote_ids')
                            ->label(__("general.Lotes"))
                            ->multiple()
                            ->options(function() {
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return Auth::user()->owner->lotes->map(function($lote) {
                                        $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                        return $lote;
                                    })->pluck('lote_name', 'lote_name')->toArray();
                                } else {
                                    return Lote::get()->map(function($lote) {
                                        $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                        return $lote;
                                    })->pluck('lote_name', 'lote_name')->toArray();
                                }
                            })
                            ->required(function(Get $get){
                                if($get('access_type')== null || !count($get('access_type'))){
                                    return false;
                                }
                                return array_search("lote", $get('access_type')) !== false ? true : false;
                            })
                            ->visible(function(Get $get){
                                if($get('access_type')== null || !count($get('access_type'))){
                                    return false;
                                }
                                return array_search("lote", $get('access_type')) !== false ? true : false;
                            })
                            ->live(),
  
                        Forms\Components\Radio::make('income_type')
                            ->label(__("general.TypeIncome"))
                            ->options(function(){
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return FormControlTypeIncome::where('status',1)
                                    ->get()->pluck('name','name')->toArray();
                                } else {
                                    return FormControlTypeIncome::where('status',1)->get()->pluck('name','name')->toArray();
                                }
                            })
                            ->columns(2)
                            ->gridDirection('row')
                            ->afterStateUpdated(function (Set $set) {
                                $set('peoples', [[
                                    // 'dni' => '',
                                    // 'first_name' => '',
                                    // 'last_name' => '',
                                    // 'phone' => '',
                                    // 'is_responsable' => false,
                                    // 'is_acompanante' => false,
                                    // 'is_menor' => false,
                                ]]);
                            })
                            ->required(function(Get $get){
                                if($get('access_type')== null || !count($get('access_type'))){
                                    return false;
                                }
                                return array_search("lote", $get('access_type')) !== false ? true : false;
                            })
                            ->visible(function(Get $get){
                                if($get('access_type')== null || !count($get('access_type'))){
                                    return false;
                                }
                                return array_search("lote", $get('access_type')) !== false ? true : false;
                            })->live(),

                Radio::make('tipo_trabajo')
                    ->options(Trabajos::get()->pluck('name','name')->toArray())
                    ->visible(function(Get $get){
                        return collect($get('income_type'))->contains('Trabajador') && !auth()->user()->hasRole('owner');
                    }),
                Forms\Components\Select::make('construction_companie_id')
                    ->options(function(){
                        return ConstructionCompanie::get()->pluck('name','id')->toArray();
                    })
                    ->visible(function(Get $get){
                        return collect($get('income_type'))->contains('Trabajador') && !auth()->user()->hasRole('owner');
                    })
                    ->live(),

            ])
            ->columns(2),

                Forms\Components\Fieldset::make('range')->label('Rango de fecha de estancia')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date_range')->label(__('general.start_date_range'))
                            ->minDate(function($context){
                                return $context == 'edit' ? '' : Carbon::now()->format('Y-m-d');
                            })
                            ->required()
                            ->live(),
                        Forms\Components\TimePicker::make('start_time_range')->label(__('general.start_time_range'))
                            ->seconds(false),
                        Forms\Components\DatePicker::make('end_date_range')->label(__('general.end_date_range'))
                            ->minDate(function(Get $get){
                                return Carbon::parse($get('start_date_range'));
                            })
                            ->required(function(Get $get){
                                return !$get('date_unilimited') ? true: false;
                            })
                            ->live(),
                        Forms\Components\TimePicker::make('end_time_range')->label(__('general.end_time_range'))->seconds(false),
                        Forms\Components\Toggle::make('date_unilimited')
                            ->label(__('general.date_unilimited'))
                            ->live()
                            ->visible(function(){
                                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                    return false;
                                }
                                return true;
                            }),
                    ])
                    ->columns(4),


                              
                CheckboxList::make('owners')->label('Trabajadores')
                    ->options(function() {
                        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                            $trabajadores = Auth::user()->owner->getAllTrabajadores();
                            
                            // Verificar que no sea null y sea una colección
                            if ($trabajadores && $trabajadores->isNotEmpty()) {
                                return $trabajadores->map(function($trabajador) {
                                    return [
                                        'id' => $trabajador->id,
                                        'name' => $trabajador->first_name . ' ' . $trabajador->last_name
                                    ];
                                })->pluck('name', 'id')->toArray();
                            }
                        }
                        return [];
                    })
                    ->visible(function(Get $get){
                            // Solo visible si está seleccionado "Trabajador" Y el usuario es owner con trabajadores
                            $isWorkerSelected = collect($get('income_type'))->contains('Trabajador');
                            
                            if (!$isWorkerSelected) {
                                return false;
                            }

                        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                            // Verificar si hay trabajadores en cualquiera de las dos relaciones
                            $hasEmployees = \App\Models\Employee::whereHas('owners', function($query) {
                                $query->where('owner_id', Auth::user()->owner_id);
                            })->exists();
                            
                            if (!$hasEmployees) {
                                $hasEmployees = \App\Models\Employee::where('owner_id', Auth::user()->owner_id)->exists();
                            }
                            
                            return $hasEmployees;
                        }
                        return false;
                    })
                    ->dehydrated(function(){
                        return true;
                    })
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $peoples = collect($get('peoples'));


                        $trabajador = \App\Models\Employee::whereIn('id', $state)->first();

                        if ($trabajador->horarios()->exists()) {
                           
                        }else{
                            Notification::make()
                                ->title('Este trabajador no tiene horarios asignados.')
                                ->body('Por favor, asigne un horario en la sección de trabajadores en el menú antes de continuar.')
                                ->danger ()
                                ->actions([
                                    Action::make('Ver trabajadores')
                                        ->button()
                                        ->url(route('filament.admin.resources.employees.index'), shouldOpenInNewTab: true)
                                ])
                                ->send();
                        }

                        
                        // Obtener trabajadores seleccionados usando ambas relaciones
                        $trabajadores = collect();
                        
                        // Primero intentar con la nueva relación
                        $employeesFromPivot = \App\Models\Employee::whereHas('owners', function($query) {
                            $query->where('owner_id', Auth::user()->owner_id);
                        })->whereIn('id', $state)->get();
                        
                        if ($employeesFromPivot->isNotEmpty()) {
                            $trabajadores = $employeesFromPivot;
                        } else {
                            // Fallback a la relación antigua
                            $trabajadores = \App\Models\Employee::where('owner_id', Auth::user()->owner_id)
                                ->whereIn('id', $state)
                                ->get();
                        }
                
                        // Eliminar trabajadores desmarcados
                        $peoples = $peoples->filter(function ($person) use ($trabajadores) {
                            $dni = trim($person['dni'] ?? '');
                            $first = trim($person['first_name'] ?? '');
                            $last = trim($person['last_name'] ?? '');

                            // eliminar registros con campos vacíos obligatorios
                            if ($dni === '' || $first === '' || $last === '') {
                                return false;
                            }

                            // Comprobar si existe un empleado con este DNI para el owner actual
                            $isEmployee = \App\Models\Employee::where(function($q){
                                $q->whereHas('owners', function($q2) {
                                    $q2->where('owner_id', Auth::user()->owner_id);
                                })->orWhere('owner_id', Auth::user()->owner_id);
                            })->where('dni', $dni)->exists();

                            // Si es empleado, mantener solo si está entre los trabajadores seleccionados
                            if ($isEmployee) {
                                return $trabajadores->contains('dni', $dni);
                            }

                            // Si no es empleado (invitado), mantener
                            return true;
                        });
                
                        // Agregar trabajadores marcados
                        foreach ($trabajadores as $trabajador) {
                            if (!$peoples->contains('dni', $trabajador->dni)) {
                                $peoples->push([
                                    'dni' => $trabajador->dni,
                                    'first_name' => $trabajador->first_name,
                                    'last_name' => $trabajador->last_name,
                                    'phone' => $trabajador->phone,
                                    'is_responsable' => false,
                                    'is_acompanante' => false,
                                    'is_menor' => false,
                                ]);
                            }
                        }
                
                        // Eliminar registros con valores nulos
                        $peoples = $peoples->filter(function ($person) {
                            return !is_null($person['dni']) && !is_null($person['first_name']) && !is_null($person['last_name']);
                        });
                
                        // Actualizar el estado de 'peoples' sin sobrescribirlo completamente
                        $set('peoples', $peoples->values()->toArray());
                    }),

                Forms\Components\Repeater::make('peoples')
                    ->label(__("general.Peoples"))
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('dni')
                            ->label(__("general.DNI"))
                            ->required()
                            ->disabled(function(Get $get){
                                return collect($get('../../income_type'))->contains('Trabajador') && auth()->user()->hasRole('owner');
                            })
                            ->dehydrated(true)
                            ->numeric(),
                                                
                                               
                        Forms\Components\TextInput::make('first_name')
                            ->label(__("general.FirstName"))
                            ->required()
                            ->disabled(function(Get $get){
                                return collect($get('../../income_type'))->contains('Trabajador') && auth()->user()->hasRole('owner');
                            })
                            ->dehydrated(true)
                            ->maxLength(255)
                            ->lazy()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Solo agregar archivo si es "Inquilino"
                                if (collect($get('../../income_type'))->contains('Inquilino') && !empty($state)) {
                                    $currentFiles = $get('../../files') ?? [];
                                    $lastName = $get('last_name') ?? '';
                                    $dni = $get('dni') ?? '';
                                    
                                    // Verificar si ya existe un archivo para este DNI
                                    $existsForDni = false;
                                    foreach ($currentFiles as $file) {
                                        if (isset($file['description']) && str_contains($file['description'], "DNI: {$dni}")) {
                                            $existsForDni = true;
                                            break;
                                        }
                                    }
                                    
                                    // Solo crear si no existe para este DNI
                                    if (!$existsForDni && !empty($dni)) {
                                        $currentFiles[] = [
                                            'description' => "DNI: {$dni} - {$state} {$lastName}",
                                            'file' => null,
                                            'user_id' => Auth::user()->id, // Agregar el user_id
                                            'form_control_id' => null // Se llenará automáticamente por la relación
                                        ];
                                        
                                        $set('../../files', $currentFiles);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('last_name')
                            ->label(__("general.LastName"))
                            ->required()
                            ->disabled(function(Get $get){
                                return collect($get('../../income_type'))->contains('Trabajador') && auth()->user()->hasRole('owner');
                            })
                            ->dehydrated(true)
                            ->maxLength(255)
                            ->lazy()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Actualizar la descripción si ya existe el archivo para este DNI
                                if (collect($get('../../income_type'))->contains('Inquilino') && !empty($state)) {
                                    $firstName = $get('first_name') ?? '';
                                    $dni = $get('dni') ?? '';
                                    
                                    if (!empty($firstName) && !empty($dni)) {
                                        $currentFiles = $get('../../files') ?? [];
                                        
                                        // Buscar y actualizar el archivo correspondiente por DNI
                                        foreach ($currentFiles as $index => $file) {
                                            if (isset($file['description']) && str_contains($file['description'], "DNI: {$dni}")) {
                                                $currentFiles[$index]['description'] = "DNI: {$dni} - {$firstName} {$state}";
                                                // Asegurar que tenga user_id
                                                if (!isset($currentFiles[$index]['user_id'])) {
                                                    $currentFiles[$index]['user_id'] = Auth::user()->id;
                                                }
                                                break;
                                            }
                                        }
                                        
                                        $set('../../files', $currentFiles);
                                    }
                                }
                            }),
                        Forms\Components\TextInput::make('phone')
                            ->label(__("general.Phone"))
                            ->tel()
                            ->numeric(),
                        Forms\Components\Toggle::make('is_responsable')->label(__("general.Responsable")),
                        Forms\Components\Toggle::make('is_acompanante')->label(__("general.Acompanante")),
                        Forms\Components\Toggle::make('is_menor')->label(__("general.Minor")),
                    ])
                    ->addable(function(Get $get){
                        return !collect($get('income_type'))->contains('Trabajador') || !auth()->user()->hasRole('owner');
                    })
                    ->columns(4)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('autos')
                    ->relationship()
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

                        Forms\Components\Hidden::make('user_id')
                            ->disabled(function($context, $record) {
                                return $context === 'edit' && $record;
                            })
                            ->default(function($context, $record) {
                                //dd($record);
                                return  Auth::user()->id ;
                            }),
                        Forms\Components\Hidden::make('model')
                            ->disabled(function($context, $record) {
                                return $context === 'edit' && $record;
                            })
                            ->default(function($context) {
                                return  'FormControl' ;
                            }),
                        ])
                    ->columns(4)
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ,

                Forms\Components\Repeater::make('files')
                    ->label('Documentos')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label(__("general.Description"))
                            ->maxLength(255),
                        Forms\Components\Hidden::make('form_control_id'),
                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        FileUpload::make('file')->required()->label('Archivo')
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->columnSpanFull()->visible(function(Get $get){
                        return !collect($get('income_type'))->contains('Trabajador');
                    }),

                Forms\Components\TextInput::make('observations')
                    ->columnSpanFull()
                    ->label(__('general.Observations')),

                Forms\Components\Hidden::make('status')
                    ->label(__("general.Status"))
                    // ->options(['Pending' => 'Pendiente','Authorized' => 'Autorizado', 'Denied' => 'Denegado'])
                    ->default('Pending')
                    // ->afterStateUpdated(function (Set $set) {
                    //     $set('authorized_user_id', Auth::user()->id);
                    // })
                    // ->readonly()
                    ->live()
                    // ->visible(Auth::user()->hasAnyRole([1]))
                    ,

                Forms\Components\Hidden::make('authorized_user_id')
                    // ->default(Auth::user()->id)
                    ->label(__("general.AuthorizedPer"))
                    ->live()
                    // ->readOnly()
                    // ->options(User::all()->pluck('name', 'id'))
                    ,

                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),

                Forms\Components\Select::make('owner_id')
                    ->relationship(name: 'owner')
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    ->label(__("general.Owner"))
                    ->default(function(){
                        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                            return Auth::user()->owner_id;
                        }
                    })
                    ->disabled(function(){
                        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                            return true;
                        }
                        return false;
                    })
                    ->dehydrated(function(){
                        if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                            return true;
                        }
                        return true;
                    })
                    // ->visible(function(){
                    //     if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    //         return false;
                    //     }
                    //     return true;
                    // })
                    ,

                Actions::make([
                    FormAction::make('aprobar')
                        ->button()
                        ->requiresConfirmation()
                        ->color('success')
                        ->label('Aprobar')
                        ->action(function(FormControl $record){

                            $record->aprobar();
                            Notification::make()
                                ->title('Formulario aprobado')
                                ->success()
                                ->send();


                                if($record->owner && $record->owner->user){
                                    Notification::make()
                                    ->title('Formulario aprobado')
                                    ->sendToDatabase($record->owner->user);
                                }
                        })
                        ->hidden(function(FormControl $record){
                            return $record->isActive() || $record->isExpirado() || $record->isVencido() ? true : false;
                        })
                        ->visible(auth()->user()->can('aprobar_form::control')),
                    FormAction::make('rechazar')
                        ->action(function(FormControl $record){
                            $record->rechazar();
                            Notification::make()
                                ->title('Formulario rechazado')
                                ->success()
                                ->send();

                                if($record->owner && $record->owner->user){
                                    Notification::make()
                                    ->title('Formulario rechazado')
                                    ->sendToDatabase($record->owner->user);
                                }
                        })
                        ->button()
                        ->requiresConfirmation()
                        ->icon('heroicon-m-hand-thumb-down')
                        ->color('danger')
                        ->label('Rechazar')
                        ->visible(auth()->user()->can('rechazar_form::control'))
                        ->hidden(function(FormControl $record){
                            return $record->isDenied() || $record->isExpirado() || $record->isVencido() ? true : false;
                        })
                ])->visible(function($context){
                    return $context == 'edit' ? true : false;
                }),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {

                    $query->where('owner_id', Auth::user()->owner_id);
                }
                return $query->orderBy('created_at', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                ->sortable()
                ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->label(__("general.Status"))
                    ->formatStateUsing(function($state, FormControl $record){
                        return $record->statusComputed();
                    })
                    // ->formatStateUsing(fn (string $state): string => match ($state) {
                    //     'Pending' => 'Pendiente',
                    //     'Authorized' => 'Autorizado',
                    //     'Denied' => 'Denegado',
                    // })
                    ->color(function($state, FormControl $record){
                        $state = $record->statusComputed();
                        $claves = [
                            'Pending' => 'warning',
                            'Authorized' => 'success',
                            'Denied' => 'danger',
                            'Vencido' => 'info',
                            'Expirado' => 'gray'
                        ];
                        return $claves[$state];
                    }),
                    // ->color(fn (string $state): string => match ($state) ),
                    Tables\Columns\TextColumn::make('lote_ids')->badge()->label(__('general.Lote')),
                Tables\Columns\TextColumn::make('access_type')
                    ->badge()
                    ->label(__("general.TypeActivitie"))
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => 'Entrada general',
                        'playa' => 'Clud playa',
                        'hause' => 'Club hause',
                        'lote' => 'Lote',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'playa' => 'gray',
                        'hause' => 'gray',
                        'lote' => 'gray',
                    })
                    ->visible(function(){
                        return Auth::user()->hasAnyRole([1]);
                    }),
                // Tables\Columns\TextColumn::make('lote_ids')->label(__('general.Lote')),
                Tables\Columns\TextColumn::make('peoples_count')->counts('peoples')->label(__('general.Visitantes')),
                // Tables\Columns\TextColumn::make('peopleResponsible.phone')
                //     ->copyable()
                //     ->label(__('general.peopleResponsiblePhone'))
                //     ->copyMessage('Phone copied')
                //     ->copyMessageDuration(1500),
                // Tables\Columns\TextColumn::make('autos_count')->counts('autos')->label('Autos'),
                //
                Tables\Columns\TextColumn::make('start_date_range')
                    ->formatStateUsing(function (FormControl $record){
                        // return '↗ '.$record->getFechasFormat()['start'].' - <br> ↘ '.$record->getFechasFormat()['end'];
                        return Carbon::parse("{$record->start_date_range} {$record->start_time_range}")->toDayDateTimeString();
                    })
                    ->searchable()
                    ->sortable()->label(__('general.start_date_range')),

                Tables\Columns\TextColumn::make('end_date_range')
                    ->formatStateUsing(function (FormControl $record){
                        // return $record->getFechasFormat()['end'];

                        return Carbon::parse("{$record->end_date_range} {$record->end_time_range}")->toDayDateTimeString();
                    })
                    ->searchable()
                    ->sortable()->label(__('general.end_date_range')),

                Tables\Columns\TextColumn::make('authorizedUser.name')
                    ->numeric()
                    ->sortable()
                    ->label(__('general.authorized_user_id'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(function(){
                        return Auth::user()->hasAnyRole([1]);
                    }),

                Tables\Columns\TextColumn::make('deniedUser.name')
                    ->numeric()
                    ->sortable()
                    ->label(__('general.denied_user_id'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(function(){
                        return Auth::user()->hasAnyRole([1]);
                    }),
                // Tables\Columns\IconColumn::make('is_moroso')
                //     ->boolean()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)

                    ,
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Filter::make('start_date_range')
                    ->label(__('general.start_date_range'))
                    ->form([
                        Section::make(__('general.start_date_range'))
                        ->schema([
                            DatePicker::make('created_from_')->label(__('general.created_from_')),
                            DatePicker::make('created_until_')->label(__('general.created_until_')),
                        ])
                    ])
                    ->columns([
                        'sm' => 2,
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from_'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date_range', '>=', $date),
                            )
                            ->when(
                                $data['created_until_'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date_range', '<=', $date),
                            );
                    }),
                Filter::make('end_date_range')
                    ->label(__('general.end_date_range'))
                    ->form([
                        Section::make(__('general.end_date_range'))
                        ->schema([
                            DatePicker::make('created_from')->label(__('general.created_from_')),
                            DatePicker::make('created_until')->label(__('general.created_until_')),
                        ])
                    ])
                    ->columns([
                        'sm' => 2,
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date_range', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date_range', '<=', $date),
                            );
                    }),

                Filter::make('created_at')
                    ->label(__('general.created_at'))
                    ->form([
                        Section::make(__('general.created_at'))
                        ->schema([
                            DatePicker::make('created_from')->label(__('general.created_from_')),
                            DatePicker::make('created_until')->label(__('general.created_until_')),
                        ])
                    ])
                    ->columns([
                        'sm' => 2,
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('status')
                    ->label(__('general.Status'))
                    ->options([
                        'Authorized' => 'Autorizado',
                        'Denied' => 'Denegado',
                        'Pending' => 'Pendiente',
                    ]),

            ])
            ->filtersFormColumns(3)
            ->actions([
                Action::make('aprobar')
                    ->action(function(FormControl $record){
                        $record->aprobar();
                        Notification::make()
                            ->title('Formulario aprobado')
                            ->success()
                            ->send();

                            if($record->owner && $record->owner->user){
                                Notification::make()
                                ->title('Formulario aprobado')
                                ->sendToDatabase($record->owner->user);
                            }
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->icon('heroicon-m-hand-thumb-up')
                    ->color('success')
                    ->label('Aprobar')
                    ->hidden(function(FormControl $record){
                        return $record->isActive() || $record->isExpirado() || $record->isVencido() ? true : false;
                    })
                    ->visible(auth()->user()->can('aprobar_form::control'))
				,
                Action::make('rechazar')
                    ->action(function(FormControl $record){
                        $record->rechazar();
                        Notification::make()
                            ->title('Formulario rechazado')
                            ->success()
                            ->send();

                            if($record->owner && $record->owner->user){
                                Notification::make()
                                ->title('Formulario rechazado')
                                ->sendToDatabase($record->owner->user);
                            }
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->icon('heroicon-m-hand-thumb-down')
                    ->color('danger')
                    ->label('Rechazar')
                    ->hidden(function(FormControl $record){
                        return $record->isDenied() || $record->isExpirado() || $record->isVencido() ? true : false;
                    })
				->visible(auth()->user()->can('rechazar_form::control'))
				,
                Tables\Actions\EditAction::make()
                    ->visible(function($record){
                        if(Auth::user()->hasRole('owner') && Auth::user()->owner_id){
                            return $record->statusComputed() == 'Pending' ? true : false;
                        }
                        return true;
                    }),
                ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                BulkAction::make('aprobar')
                    ->label('Aprobar')
                    ->color('success')
                    ->icon('heroicon-m-hand-thumb-up')
                    ->requiresConfirmation()
                    ->visible(auth()->user()->can('aprobar_form::control'))
                    ->action(function (Collection $records){
                        $records->each->aprobar();
                        Notification::make()
                            ->title('Formularios aprobados')
                            ->success()
                            ->send();
                    }),
                BulkAction::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->requiresConfirmation()
                    ->visible(auth()->user()->can('rechazar_form::control'))
                    ->action(function (Collection $records){
                        $records->each->rechazar();
                        Notification::make()
                            ->title('Formularios aprobados')
                            ->success()
                            ->send();
                    })
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
            'index' => Pages\ListFormControls::route('/'),
            'create' => Pages\CreateFormControl::route('/create'),
            'edit' => Pages\EditFormControl::route('/{record}/edit'),
            'view' => Pages\ViewFormControl::route('/{record}'),
        ];
    }
}
