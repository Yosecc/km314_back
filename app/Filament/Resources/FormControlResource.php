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
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Actions\Action as NotificationAction;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\Placeholder;
use App\Models\Employee;

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

    public static function tiposFormulario()
    {
        return [
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
                        // ->hidden(function(){
                        //     if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                        //         return true;
                        //     }
                        //     return true;
                        // })
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
                        ->default(function(){
                            if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                                return Auth::user()->owner->lotes->map(function($lote) {
                                    return $lote->sector->name . $lote->lote_id;
                                })->toArray();
                            }
                            return [];
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
                        ->columns(3)
                        ->gridDirection('row')
                        ->columnSpan(2)
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('peoples', [[
                                // 'dni' => '',
                                // 'first_name' => '',
                                // 'last_name' => '',
                                // 'phone' => '',
                                // 'is_responsable' => false,
                                // 'is_acompanante' => false,
                                // 'is_menor' => false,
                            ]]);

                                if($state == 'Visita Temporal (24hs)'){
                                    $set('start_date_range', Carbon::now()->format('Y-m-d'));
                                    $set('start_time_range', Carbon::now()->format('H:i'));
                                    $set('end_date_range', Carbon::now()->addDay()->format('Y-m-d'));
                                    $set('end_time_range', Carbon::now()->format('H:i'));
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
                ->columns(4),
        ];
    }

    public static function fechasFormulario()
    {
        return [
            
            Forms\Components\Fieldset::make('range')->label('Rango de fecha de estancia')
                ->schema([
                    Forms\Components\DatePicker::make('start_date_range')->label(__('general.start_date_range'))
                        ->minDate(function($context){
                            return $context == 'edit' ? '' : Carbon::now()->format('Y-m-d');
                        })
                        ->required()
                        ->disabled(function(Get $get){
                            return $get('income_type') == 'Visita Temporal (24hs)';
                        })
                        ->dehydrated()
                        ->live(),
                    Forms\Components\TimePicker::make('start_time_range')
                        ->label(__('general.start_time_range'))
                        ->required()
                        ->disabled(function(Get $get){
                            return $get('income_type') == 'Visita Temporal (24hs)';
                        })
                        ->dehydrated()
                        ->seconds(false),
                    Forms\Components\DatePicker::make('end_date_range')->label(__('general.end_date_range'))
                        ->minDate(function(Get $get){
                            return Carbon::parse($get('start_date_range'));
                        })
                        ->required(function(Get $get){
                            return !$get('date_unilimited') ? true: false;
                        })
                        ->disabled(function(Get $get){
                            return $get('income_type') == 'Visita Temporal (24hs)';
                        })
                        ->dehydrated()
                        ->live(),
                    Forms\Components\TimePicker::make('end_time_range')
                        ->label(__('general.end_time_range'))
                        ->required()
                        ->disabled(function(Get $get){
                            return $get('income_type') == 'Visita Temporal (24hs)';
                        })
                        ->dehydrated()
                        ->seconds(false),

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
        ];
    }

    public static function personasFormulario()
    {
        return [
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

                    if($get('start_date_range') == null || $get('start_time_range') == null || $get('end_date_range') == null || $get('end_time_range')  == null){
                        Notification::make()
                            ->title('Seleccione primero el rango de fechas y horas.')
                            ->danger()
                            ->send();
                            $set('owners', []);
                        return; 
                    }
                    // dd($state);
                    if(count($state)){
                        $trabajadores = \App\Models\Employee::whereIn('id', $state)->get();
                        $allHaveHorarios = true;
                        $failedId = null;
                        $trabajadores->each(function(Employee $trabajador) use (&$allHaveHorarios, &$failedId, $get, $set, $state) {

                            if($trabajador->vencidosAutosFile()){
                                Notification::make()
                                    ->title('El trabajador '.$trabajador->nombres().' tiene vehículos con documentos vencidos.')
                                    ->body('Por favor, verifique la documentacion de los vehículos del trabajador.')
                                    ->danger()
                                    ->actions([
                                        NotificationAction::make('Ver a '.$trabajador->nombres())
                                            ->button()
                                            ->url(route('filament.admin.resources.employees.view', $trabajador), shouldOpenInNewTab: true),
                                    ])
                                    ->send();
                                $allHaveHorarios = false;
                                $failedId = $trabajador->id;

                            }

                            if($trabajador->vencidosFile()){
                                Notification::make()
                                    ->title('El trabajador '.$trabajador->nombres().' tiene documentos vencidos.')
                                    ->body('Por favor, verifique la documentación del trabajador.')
                                    ->danger()
                                    ->actions([
                                        NotificationAction::make('Ver a '.$trabajador->nombres())
                                            ->button()
                                            ->url(route('filament.admin.resources.employees.view', $trabajador), shouldOpenInNewTab: true),
                                    ])
                                    ->send();
                                $allHaveHorarios = false;
                                $failedId = $trabajador->id;
                            }

                            if($trabajador->isVencidoSeguro()){
                                Notification::make()
                                    ->title('El trabajador '.$trabajador->nombres().' requiere una reeverificación.')
                                    ->body('Por favor, verifique la documentación del trabajador.')
                                    ->danger()
                                    ->actions([
                                        NotificationAction::make('Ver a '.$trabajador->nombres())
                                            ->button()
                                            ->url(route('filament.admin.resources.employees.view', $trabajador), shouldOpenInNewTab: true),
                                    ])
                                    ->send();
                                $allHaveHorarios = false;
                                $failedId = $trabajador->id;
                            } 

                            if (!$trabajador->horarios()->exists()) {
                                Notification::make()
                                    ->title('Este trabajador no tiene horarios asignados.')
                                    ->body('Por favor, asigne un horario en la sección de trabajadores en el menú antes de continuar.')
                                    ->danger()
                                    ->actions([
                                        NotificationAction::make('Ver trabajadores')
                                            ->button()
                                            ->url(route('filament.admin.resources.employees.index'), shouldOpenInNewTab: true),
                                        NotificationAction::make('Ver a '.$trabajador->first_name)
                                            ->button()
                                            ->url(route('filament.admin.resources.employees.view', $trabajador), shouldOpenInNewTab: true),
                                    ])
                                    ->send();
                                $allHaveHorarios = false;
                                $failedId = $trabajador->id;
                            } else {
                                // Aquí tu lógica adicional
                                
                                $startDate = Carbon::parse($get('start_date_range') . ' ' . $get('start_time_range'));
                                $endDate = Carbon::parse($get('end_date_range') . ' ' . $get('end_time_range'));

                                // Obtener días de la semana configurados en los horarios del trabajador
                                $diasDisponibles = $trabajador->horarios->pluck('day_of_week')->unique()->values()->toArray();

                                // Mapear días de Carbon a español (ajusta si tus días están en otro idioma)
                                $carbonToDb = [
                                    'Sunday' => 'Domingo',
                                    'Monday' => 'Lunes',
                                    'Tuesday' => 'Martes',
                                    'Wednesday' => 'Miércoles',
                                    'Thursday' => 'Jueves',
                                    'Friday' => 'Viernes',
                                    'Saturday' => 'Sábado',
                                ];

                                // Recorrer el rango de fechas y ver si hay coincidencia de día
                                $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
                                $hayCoincidencia = false;
                                foreach ($period as $date) {
                                    $dia = $carbonToDb[$date->format('l')];
                                    if (in_array($dia, $diasDisponibles)) {
                                        $hayCoincidencia = true;
                                        break;
                                    }
                                }

                                if (!$hayCoincidencia) {
                                    $allHaveHorarios = false;
                                    $failedId = $trabajador->id;

                                    Notification::make()
                                        ->title('Rango de fechas no válido para el trabajador')
                                        ->body('Los días disponibles para este trabajador son: ' . implode(', ', $diasDisponibles) . '. Ajusta el rango de fechas para coincidir con alguno de estos días.')
                                        ->danger()
                                        ->send();
                                    // Aquí puedes quitar el trabajador del owners si lo deseas
                                    // $set('owners', array_values(array_filter($state, fn($id) => $id != $trabajador->id)));
                                    // return;
                                }
                            }

                            $autos = $get('autos') ?? [];
                            $autosTrabajador = $trabajador->autos;
                            $autosform = $autosTrabajador->map(function($auto){
                                return [
                                    'marca' => $auto->marca,
                                    'modelo' => $auto->modelo,
                                    'patente' => $auto->patente,
                                    'color' => $auto->color,
                                    'isfiles' => false,
                                ];
                            });

                            $set('autos', array_merge($autos, $autosform->toArray()));
                        
                        });


                        if (!$allHaveHorarios && $failedId) {
                            // Quitar el id que falló del estado de owners
                            $newOwners = array_filter($state, fn($id) => $id != $failedId);
                            $set('owners', array_values($newOwners));
                            return;
                        }
                    }

                    // Obtener trabajadores seleccionados usando ambas relaciones
                    $trabajadores = collect();
                    
                    // Primero intentar con la nueva relación
                    $employeesFromPivot = Employee::whereHas('owners', function($query) {
                        $query->where('owner_id', Auth::user()->owner_id);
                    })->whereIn('id', $state)->get();
                    
                    if ($employeesFromPivot->isNotEmpty()) {
                        $trabajadores = $employeesFromPivot;
                    } else {
                        // Fallback a la relación antigua
                        $trabajadores = Employee::where('owner_id', Auth::user()->owner_id)
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
                ->label('Cargue los datos de las personas que ingresarán al barrio')
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
                            // if (collect($get('../../income_type'))->contains('Inquilino') && !empty($state)) {
                            //     $currentFiles = $get('../../files') ?? [];
                            //     $lastName = $get('last_name') ?? '';
                            //     $dni = $get('dni') ?? '';
                                
                            //     // Verificar si ya existe un archivo para este DNI
                            //     $existsForDni = false;
                            //     foreach ($currentFiles as $file) {
                            //         if (isset($file['description']) && str_contains($file['description'], "DNI: {$dni}")) {
                            //             $existsForDni = true;
                            //             break;
                            //         }
                            //     }
                                
                            //     // Solo crear si no existe para este DNI
                            //     if (!$existsForDni && !empty($dni)) {
                            //         $currentFiles[] = [
                            //             'description' => "DNI: {$dni} - {$state} {$lastName}",
                            //             'file' => null,
                            //             'user_id' => Auth::user()->id, // Agregar el user_id
                            //             'form_control_id' => null // Se llenará automáticamente por la relación
                            //         ];
                                    
                            //         $set('../../files', $currentFiles);
                            //     }
                            // }
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
                            // if (collect($get('../../income_type'))->contains('Inquilino') && !empty($state)) {
                            //     $firstName = $get('first_name') ?? '';
                            //     $dni = $get('dni') ?? '';
                                
                            //     if (!empty($firstName) && !empty($dni)) {
                            //         $currentFiles = $get('../../files') ?? [];
                                    
                            //         // Buscar y actualizar el archivo correspondiente por DNI
                            //         foreach ($currentFiles as $index => $file) {
                            //             if (isset($file['description']) && str_contains($file['description'], "DNI: {$dni}")) {
                            //                 $currentFiles[$index]['description'] = "DNI: {$dni} - {$firstName} {$state}";
                            //                 // Asegurar que tenga user_id
                            //                 if (!isset($currentFiles[$index]['user_id'])) {
                            //                     $currentFiles[$index]['user_id'] = Auth::user()->id;
                            //                 }
                            //                 break;
                            //             }
                            //         }
                                    
                            //         $set('../../files', $currentFiles);
                            //     }
                            // }
                        }),
                    Forms\Components\TextInput::make('phone')
                        ->label(__("general.Phone"))
                        ->tel()
                        ->numeric(),
                    Forms\Components\Toggle::make('is_responsable')->default(false)->label(__("general.Responsable")),
                    Forms\Components\Toggle::make('is_acompanante')->default(false)->label(__("general.Acompanante")),
                    Forms\Components\Toggle::make('is_menor')->default(false)->label(__("general.Minor")),
                    
                    FileUpload::make('file_dni')->required()->label('DNI')
                        ->required(function(Get $get){
                            return collect($get('../../income_type'))->contains('Inquilino');
                        })
                        ->visible(function(Get $get){
                            return collect($get('../../income_type'))->contains('Inquilino');
                        }),

                ])
                ->addable(function(Get $get){
                    return !collect($get('income_type'))->contains('Trabajador') || !auth()->user()->hasRole('owner');
                })
                ->itemLabel(fn (array $state): ?string => $state['first_name'] ?? null)
                ->columns(4)
                ->addActionLabel('Agregar persona')
                ->columnSpanFull(),
        ];
    }
    
    public static function autosFormulario()
    {
        return [
            Forms\Components\Repeater::make('autos')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('marca')
                        ->label(__("general.Marca"))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('modelo')
                        ->label(__("general.Modelo"))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('patente')
                        ->label(__("general.Patente"))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('color')
                        ->label(__("general.Color"))
                        ->required()
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
                    Repeater::make('files')
                        ->relationship()
                        ->label('Documentos del vehículo')
                        ->schema([
                            Forms\Components\Hidden::make('name')->dehydrated(),
                            DatePicker::make('fecha_vencimiento')
                                ->label('Fecha de vencimiento del documento')
                                ->extraInputAttributes(function(Get $get, $state){
                                    if(Carbon::parse($state)->isPast()){
                                        return ['style' => 'border-color: red;'];
                                    }
                                    return [];
                                })
                                ->required(),
                            Forms\Components\FileUpload::make('file')
                                ->label('Archivo')
                                ->required()
                                ->storeFileNamesIn('attachment_file_names')
                                ->openable()
                                ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                    return $file ? $file->getClientOriginalName() : $record->file;
                                })
                        ])
                        ->defaultItems(3)
                        ->minItems(3)
                        ->maxItems(3)
                        ->addable(false)
                        ->deletable(false)
                        ->disabled(function(Get $get){
                            $isfiles = $get('isfiles');
                            return $isfiles !== null && $isfiles === false;
                        })
                        ->grid(3)
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->default([
                            [
                                'name' => 'Seguro del Vehículo',
                            ],
                            [
                                'name' => 'VTV',
                            ],
                            [
                                'name' => 'Cédula del Vehículo',
                            ],
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->defaultItems(0)
                ->columnSpanFull()
        ];
    }

    public static function informacionExtraFormulario()
    {
        return [
            Forms\Components\Repeater::make('files')
                ->label('Otros documentos')
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
                ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                ->defaultItems(0)
                ->columnSpanFull()->visible(function(Get $get){
                    return !collect($get('income_type'))->contains('Trabajador');
                }),

            Forms\Components\Toggle::make('bring_mascotas')
                ->label('¿Traerá mascotas?')
                ->default(function(Get $get){
                    return $get('mascotas') && count($get('mascotas')) > 0 ? true : false;
                })
                ->live(),

            Forms\Components\Repeater::make('mascotas')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('tipo_mascota')
                        ->label(__("general.TypePet"))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('raza')
                        ->label(__("general.Breed"))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nombre')
                        ->label(__("general.NamePet"))
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_vacunado')
                        ->label(__("general.IsVaccinated")),
                ])
                ->visible(function(Get $get , $context){
                    if($context === 'edit' || $context === 'view'){
                        return true;
                    }
                    return $get('bring_mascotas') ? true : false;
                })
                ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                ->columns(4)
                ->defaultItems(0)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('observations')
                ->columnSpanFull()
                ->label(__('general.Observations')),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('')->content('Completa la siguiente información para crear un formulario de control del acceso.')->columnSpanFull(),
                Wizard::make([
                    Wizard\Step::make('Paso 1')
                        ->description('Tipo y fechas')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            ...self::tiposFormulario(),
                            ...self::fechasFormulario(),
                        ]),

                    Wizard\Step::make('Paso 2')
                        ->description('Personas')
                        ->icon('heroicon-m-user-group')
                        ->schema([
                            ...self::personasFormulario(),
                        ]),
                    
                    Wizard\Step::make('Paso 3')
                        ->description('Autos')
                        ->icon('heroicon-m-truck')
                        ->schema([
                            ...self::autosFormulario(),
                        ]),

                    Wizard\Step::make('Paso  4')
                        ->description('Información Extra')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            ...self::informacionExtraFormulario(),
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
                                }),
                            Forms\Components\Hidden::make('status')
                                ->label(__("general.Status"))
                                ->default('Pending')
                                ->live(),

                            Forms\Components\Hidden::make('authorized_user_id')
                                ->label(__("general.AuthorizedPer"))
                                ->live(),

                            Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        ]),

                ])
                ->skippable(function($context){
                    return $context === 'edit' || $context === 'view' ? true : false;
                })
                ->columnSpanFull(), 
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
                Tables\Columns\TextColumn::make('peoples_count')->counts('peoples')->label(__('general.Visitantes')),
                Tables\Columns\TextColumn::make('start_date_range')
                    ->formatStateUsing(function (FormControl $record){
                        return Carbon::parse("{$record->start_date_range} {$record->start_time_range}")->toDayDateTimeString();
                    })
                    ->searchable()
                    ->sortable()->label(__('general.start_date_range')),

                Tables\Columns\TextColumn::make('end_date_range')
                    ->formatStateUsing(function (FormControl $record){
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
