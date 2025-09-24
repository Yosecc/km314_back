<?php

namespace App\Filament\Resources;

use App\Actions\ResetStars;
use App\Actions\Star;
use App\Filament\Resources\ActivitiesResource\Pages;
use App\Filament\Resources\ActivitiesResource\RelationManagers;
use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Models\Auto;
use App\Models\Employee;
use App\Models\EmployeeAutos;
use App\Models\FormControl;
use App\Models\FormControlAuto;
use App\Models\FormControlPeople;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\OwnerAutos;
use App\Models\OwnerFamily;
use App\Models\OwnerSpontaneousVisit;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ActivitiesResource extends Resource
{
    protected static ?string $model = Activities::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationLabel = 'Entradas/Salidas';

    protected static ?string $label = 'Entrada/Salida';

    protected static ?string $navigationGroup = 'Control de acceso';

    protected static $PARAMS = null;

    public static function getPluralModelLabel(): string
    {
        return 'Entradas/Salidas';
    }

    public static function searchEmployee($dni, $type, $ids = [])
    {

        $data = Employee::where('dni', 'like', '%'.$dni.'%')->orWhere(function($query) use ($dni) {
            $query->whereHas('autos', function ($query) use ($dni){
                $query->where('patente','like','%'.$dni.'%');
            });
        })->where('model_origen','Employee')->limit(10)->get();


        $mapeo = function($employee) use ($type){

            // dd($employee->isFormularios());

            if($type == 'option'){
                $employee['texto'] = $employee['first_name']. ' '.$employee['last_name'];
                $employee['texto'].= ' - '.__('general.Employee');
            }else{
                $employee['texto'] = $employee->dni;
                $employee['texto'] .= ' - '.$employee->work->name;
            }

            return $employee;
        };

        if(count($ids)){
            $data = Employee::whereIn('id', $ids)->get()->map($mapeo);
        }

        $data = $data->map($mapeo);

        return $data->pluck('texto','id')->toArray();
    }

    public static function searchOwners($dni, $type , $ids = [])
    {
        $data = Owner::where('dni', 'like', '%'.$dni.'%')
            ->orWhere(function($query) use ($dni) {
                $query->whereHas('autos', function ($query) use ($dni){
                    $query->where('patente','like','%'.$dni.'%');
                });
        })->limit(10)->get();

        $mapeo = function($people) use ($type){

            if($type == 'option'){
                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                // $people['texto'].= ' - Propietario';
            }else{

                $people['texto'] = $people->dni;
                $people['texto'] .= '- Propietario - ' . ($people->status ? $people->status['name'] : '');
            }

            return $people;
        };

        if(count($ids)){
            $data = Owner::whereIn('id', $ids)->get()->map($mapeo);
        }

        $data = $data->map($mapeo);

        $datos = $data->pluck('texto','id')->toArray();

        // $familiares = self::searchOwnerFamily($dni, $type , $ids);

        // dd(, $datos);

        return $datos;
    }

    public static function searchOwnerFamily($dni, $type , $ids = [], $owner_id)
    {
        // Buscar familiares por DNI
        $dniMatches = OwnerFamily::where('dni','like','%'.$dni.'%')->get();
        $ownerIds = $dniMatches->pluck('owner_id')->unique()->filter();

        // Si hay coincidencias por DNI, traer toda la familia de esos owner_id
        if ($dniMatches->count() > 0) {
            $data = OwnerFamily::whereIn('owner_id', $ownerIds)->get();
        } else if ($owner_id != 0) {
            $data = OwnerFamily::where('owner_id', $owner_id)->get();
        } else if (count($ids)) {
            $data = OwnerFamily::whereIn('id', $ids)->get();
        } else {
            $data = collect();
        }

        $mapeo = function($people) use ($type){
            if($type == 'option'){
                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                $people['texto'].= ' - ' .$people['parentage'] ;
            }else{
                $people['texto'] = $people->dni;
                $people['texto'] .= ' - Familiar de: '.$people->familiarPrincipal->first_name . " " . $people->familiarPrincipal->last_name ;
            }

            return $people;
        };

        $data = $data->map($mapeo);
        return $data->pluck('texto','id')->toArray();

    }

    public static function searchOwnersAutos($ids, $type)
    {
        $owners = Owner::whereIn('id',$ids)->get();

        return $owners->map(function($owner) use ($type){
                return $owner->autos->map(function($auto) use ($type){
                    if($type == 'option'){
                        $auto['texto'] = $auto['marca']. ' - '.$auto['modelo'];
                    }else{
                        $auto['texto'] = $auto['patente'].' - '.$auto['color'];
                    }
                    return $auto;
                });
            })->collapse()->pluck('texto','id')->toArray();
    }

    public static function searchEmployeeAutos($ids, $type)
    {
        $datas = Employee::whereIn('id',$ids)->get();

        return $datas->map(function($data) use ($type){
                return $data->autos->map(function($auto) use ($type){
                    if($type == 'option'){
                        $auto['texto'] = $auto['marca']. ' - '.$auto['modelo'];
                    }else{
                        $auto['texto'] = $auto['patente'].' - '.$auto['color'];
                    }
                    return $auto;
                });
            })->collapse()->pluck('texto','id')->toArray();
    }

    public static function searchFormControl($id, $type, $ids = [])
    {
        $formControl = FormControl::find($id);

        $mapeo = function($people) use($type){
            if($type == 'option'){
                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
            }else{
                $people['texto'] = $people->dni;
            }
            return $people;
        };

        if(count($ids)){
            return FormControlPeople::whereIn('id', $ids)->get()->map($mapeo)->pluck('texto','id')->toArray();
        }

        return $formControl->peoples->map($mapeo)->pluck('texto','id')->toArray();
    }

    public static function searchFormAutos($id, $type)
    {
        $data = FormControl::find($id);

        return $data->autos->map(function($auto) use ($type){
            if($type == 'option'){
                $auto['texto'] = $auto['marca']. ' - '.$auto['modelo'];
            }else{
                $auto['texto'] = $auto['patente'].' - '.$auto['color'];
            }
            return $auto;
        })->pluck('texto','id')->toArray();
    }


    public static function searchAutos($ids, $type)
    {
        return Auto::whereIn('id',$ids)->get()->map(function($auto) use ($type){
            if($type == 'option'){
                $auto['texto'] = $auto['marca']. ' - '.$auto['modelo'];
            }else{
                $auto['texto'] = $auto['patente'].' - '.$auto['color'];
            }
            return $auto;
        })->pluck('texto','id')->toArray();
    }

    public static function searchAutosModel($ids, $type, $model)
    {
        return Auto::whereIn('model_id',$ids)->where('model', $model)->get()->map(function($auto) use ($type){
            if($type == 'option'){
                $auto['texto'] = $auto['marca']. ' - '.$auto['modelo'];
            }else{
                $auto['texto'] = $auto['patente'].' - '.$auto['color'];
            }
            return $auto;
        })->pluck('texto','id')->toArray();
    }




       
    public static function createAuto($data, $config)
    {
        $data = collect($data)->map(function($auto) use ($config){
            $auto['user_id'] = Auth::user()->id;
            $auto['created_at'] = Carbon::now();
            $auto['updated_at'] = Carbon::now();
            
            // Limpiar campos que no van a la tabla
            unset($auto['familiar_model_id']);
            unset($auto['espontaneo_model_id']);
            
            // Validar que model_id no sea null
            // if (empty($auto['model_id'])) {
            //     throw new \Exception('Debe seleccionar el responsable del vehículo');
            // }
            
            // Asegurar que model esté definido
            if (empty($auto['model'])) {
                $auto['model'] = match($config['type']) {
                    1 => 'Owner',
                    2 => 'Employee', 
                    3 => 'FormControl',
                    default => 'Owner'
                };
            }
    
            return $auto;
        });
        
        Auto::insert($data->toArray());
    }

    public static function createSpontaneusVisit($data)
    {
        $owner_id = $data['owner_id'];
        // dd($owner_id);
        $visitantes = collect($data['spontaneous_visit']);

        $visitantes = $visitantes->map(function($visitante) use ($owner_id) {
            return [
                'owner_id' => $owner_id,
                'dni' => $visitante['dni'],
                'first_name' => $visitante['first_name'],
                'last_name' => $visitante['last_name'],
                'email' => $visitante['email'],
                'phone' => $visitante['phone'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        OwnerSpontaneousVisit::insert($visitantes->toArray());

    }

        
    public static function getPeoples($data)
    {
        self::$PARAMS = [
            'num_search' => $data['num_search'] ?? '',
            'tipo_entrada' => $data['tipo_entrada'] ?? null,
            'form_control_id' => $data['form_control_id'] ?? null // Agregar default null
        ];
    
        if( $data['tipo_entrada'] == 2){
            return $data['num_search'] || count($data['ids']) ? self::searchEmployee($data['num_search'], $data['tipo'], $data['ids']) : [];
        }
    
        if( $data['tipo_entrada'] == 1){
            return $data['num_search'] || count($data['ids']) ? self::searchOwners($data['num_search'], $data['tipo'], $data['ids']) : [];
        }
    
        if( $data['tipo_entrada'] == 3 && isset($data['form_control_id']) && $data['form_control_id']){
            return $data['num_search'] || count($data['ids']) ? self::searchFormControl($data['form_control_id'],  $data['tipo'], $data['ids']) : [];
        }
    
        return [];
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                    Grid::make([
                        'default' => 3,
                    ])
                    ->schema([

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'Entry' => __('general.Entry'),
                                'Exit' => __('general.Exit'),
                            ])
                            ->label(__('general.Type'))
                            ->disabled(true)
                            ->visible(function($context){
                                return $context == 'view' ? true : false;
                            })
                            ->default(isset($_GET['type']) ? $_GET['type']  : '' ),

                        DateTimePicker::make('created_at')
                            ->label('Fecha de entrada')
                            ->disabled(true)
                            ->visible(function($context){
                                return $context == 'view' ? true : false;
                            }),

                    ]),
                Forms\Components\ViewField::make('type')
                    ->required()
                    ->view('filament.forms.components.tipoActividad')
                    ->label(__('general.Type'))
                    ->default(function($state, $context){

                        if(!isset($_GET['type'])){
                            return '';
                        }

                        if($_GET['type']  == 'Entry'){
                            return 1;
                        }
                        if($_GET['type']  == 'Exit'){
                            return 2;
                        }
                        return 0;
                    })
                    ->viewData([
                        'opciones' => [
                            1 => __('general.Entry'),
                            2 => __('general.Exit'),
                        ],
                    ])
                    ->disabled(function($context){
                        return $context == 'view' ? true : false;
                    })
                    ->visible(function($context){
                        return $context == 'view' ? false : true;
                    })
                    ->live(),
                /**
                 * TIPO DE ENTRADA
                 */
                Forms\Components\Fieldset::make()
                    ->schema([
                        Forms\Components\ViewField::make('tipo_entrada')
                            ->required()
                            ->view('filament.forms.components.tipoEntrada')
                            ->viewData([
                                'opciones' => [ 1 => 'Propietarios', 2 => 'Empleados', 3 => 'Otros' ] ,
                            ])
                            ->disabled(function($context){
                                return $context == 'view' ? true : false;
                            })
                            ->live()
                    ])
                    ->columns(1),

                /**
                 * BUSCADOR
                 */
                Forms\Components\Fieldset::make()
                    ->label(__('general.Search'))
                    ->columns([
                        'sm' => 4,
                        'xl' => 6,
                        '2xl' => 8,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('num_search')
                            ->label(__('general.DNI')."/Patente")
                            ->columnSpan(['sm'=> 2])
                            ->columnStart([
                                'sm' => 2,
                                'xl' => 3,
                                '2xl' => 4,
                            ])
                            ->extraAttributes(['onkeydown' => "if(event.key === 'Enter') { event.preventDefault(); return false; }"])
                            ->extraInputAttributes(['class' => 'inputDNI', 'style' => 'height: 50px;text-align: center;font-size: 20px;font-weight: 900;'])
                            ->live(),
                    ])
                    ->visible(function(Get $get, $context){
                        return $get('tipo_entrada') && $context != 'view' ? true: false;
                    }),
                /**
                 * ///////////
                 * FORMULARIOS
                 */
                Forms\Components\Fieldset::make('forms_control')->label(__('general.Forms Control'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\Radio::make('form_control_id')->label(__('general.Select a control form'))
                                    ->required()
                                    ->hint(function($state, Get $get){
                                        if(!$state){
                                            return '';
                                        }
                                        return new HtmlString('<a target="_blank" href="/form-controls/'.$state.'">Ver fomulario seleccionado</a>');
                                    })
                                    ->options(function(Get $get,  $context ){
                                        if(!$get('num_search') && !$get('form_control_id')){
                                            return [];
                                        }


                                        $mapeo = function($form){
                                            $accesType = collect($form['access_type'])->map(function($type){
                                                $data = ['general' => 'Entrada general', 'playa' => 'Clud playa', 'hause' => 'Club hause', 'lote' => 'Lote' ];
                                                return $data[$type];
                                            })->implode(' - ');

                                            $lotes = collect($form['lote_ids'])->implode(' - ');
                                            $income = collect($form['income_type'])->implode(' - ');

                                            $income = $income !=''  ? $income.' / ': $income;
                                            $lotes = $lotes !=''  ? ' : '.$lotes: $lotes;

                                            $formInfo = 'Form: '.$form['id'].' -- ';

                                            $form['texto'] = $formInfo.$income.$accesType.$lotes;
                                            $form['status'] = $form->statusComputed();


                                            return $form;
                                        };

                                        if($get('form_control_id') && $context != 'create'){
                                            return FormControl::where('id',$get('form_control_id'))->get()->map($mapeo)->pluck('texto','id')->toArray();
                                        }

                                        $num = $get('num_search');
                                        return FormControl::whereHas('peoples', function ($query) use ($num)
                                            {
                                                $query->where('dni','like','%'.$num.'%');
                                            })
                                            ->orWhere(function($query) use ($num) {
                                                $query->whereHas('autos', function ($query) use ($num){
                                                    $query->where('patente','like','%'.$num.'%');
                                                });
                                            })
                                            ->orderBy('id','desc')
                                            ->where('start_date_range','>=',now())
                                            ->limit(10)
                                            ->get()->map(callback: $mapeo)
                                        //->whereNotIn('status',['Vencido','Expirado'])
                                        ->pluck('texto','id')->toArray();
                                    })
                                    ->descriptions(function(Get $get, Set $set, $context){
                                        if(!$get('num_search') && !$get('form_control_id')){
                                            return [];
                                        }

                                        $mapeo = function($form){

                                            $fechas = $form->getFechasFormat();

                                            $limite =  $form['date_unilimited'] ?  'Sin fecha límite de salida': $fechas['end'];

                                            $observacion = $form['observations'] ? ' ( Observaciones: '. $form['observations'] .' )' : '';

                                            $form['status'] = $form->statusComputed();

                                            $form['texto'] = __('general.'.$form->statusComputed()).' - '.$fechas['start'].' / '. $limite . $observacion;
                                            return $form;
                                        };

                                        if($get('form_control_id') && $context != 'create'){
                                            return FormControl::where('id',$get('form_control_id'))->get()->map($mapeo)->pluck('texto','id')->toArray();
                                        }

                                        $num = $get('num_search');
                                        return FormControl::whereHas('peoples', function ($query) use ($num)
                                            {
                                                $query->where('dni','like','%'.$num.'%');
                                            })
                                            ->orWhere(function($query) use ($num) {
                                                $query->whereHas('autos', function ($query) use ($num){
                                                    $query->where('patente','like','%'.$num.'%');
                                                });
                                            })
                                            ->orderBy('id','desc')
                                            ->whereDate('start_date_range','>=',now())
                                            ->limit(10)->get()->map($mapeo)
                                        // ->whereNotIn('status',['Vencido','Expirado'])
                                        ->pluck('texto','id')->toArray();

                                    })
                                    ->disableOptionWhen(function ( $value){
                                        $form = FormControl::find($value);
                                        return !$form->isActive();
                                    })
                                    ->live(),

                                Forms\Components\Radio::make('lote_ids')
                                    ->required()
                                    ->label(__('general.SelectedLote'))
                                    ->options(function(Get $get){
                                        $formControl = FormControl::find($get('form_control_id'));
                                        return Lote::get()->map(function($lote){
                                            $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                            return $lote;
                                        })->whereIn('lote_name',$formControl->lote_ids)->pluck('lote_name', 'lote_name')->toArray();
                                    })
                                    ->visible(function(Get $get){
                                        if(!$get('form_control_id')){
                                            return false;
                                        }
                                        $formControl = FormControl::find($get('form_control_id'));
                                        return array_search("lote", $formControl->access_type) !== false ? true : false;
                                    })
                            ])
                    ])
                    ->visible(function($state, Get $get){
                        return ($get('tipo_entrada') == 3 )? true : false;
                    }),
                /**
                 * LISTA DE PERSONAS
                 */
                Forms\Components\Fieldset::make('peoples_list')->label(__('general.Peoples'))
                    ->columns(2)
                    ->schema([  

                        /**
                         * PERSONAS
                         */
                        Forms\Components\CheckboxList::make('peoples')->label(__('general.Select people one or more options'))
                            ->searchable()
                            ->options(function(Get $get, $context, $record){

                                $peoplesIds = $get('peoples');
                                 if($context == 'view' && isset($peoplesIds) && !count($peoplesIds) && $record->peoples){
                                    //  $peoplesIds = $record->peoples->pluck('model_id')->toArray();

                                    $peoplesIds = $record->peoples->map(function($peopleActivitie){
                                        // dd($peopleActivitie);
                                        if($peopleActivitie->type == 'Employee'){
                                            $info = $peopleActivitie->getPeople();
                                            if($info){
                                                $employee = Employee::where('dni',$info->dni)->first();
                                                if($employee){
                                                    $peopleActivitie->model_id = $employee->id;
                                                    $peopleActivitie->model = 'Employee';
                                                }
                                            }
                                        }

                                        return $peopleActivitie;
                                     })->pluck('model_id')->toArray();

                                 }
                                //  dd( $record->peoples, $peoplesIds );
                                 return self::getPeoples([
                                     'tipo_entrada' => $get('tipo_entrada'),
                                     'num_search' => $get('num_search') ,
                                     'form_control_id' => $get('form_control_id'),
                                     'tipo' => 'option',
                                     'ids' =>  $context == 'view' ? $peoplesIds : [],
                                     'context' => $context
                                 ]);
                             })
                             ->descriptions(function(Get $get , $context, $record){
                                 $peoples = $get('peoples');
                                 if($context == 'view' && isset($peoples) && !count($peoples) && $record->peoples){
                                     $peoples = $record->peoples->pluck('model_id')->toArray();
                                 }
                                 return self::getPeoples([
                                     'tipo_entrada' => $get('tipo_entrada'),
                                     'num_search' => $get('num_search') ,
                                     'form_control_id' => $get('form_control_id'),
                                     'tipo' => 'descriptions',
                                     'ids' =>  $context == 'view' ? $peoples : [],
                                     'context' => $context
                                 ]);
                             })
                             ->visible(function(Get $get, $context, $record  ){

                                 $peoples = $get('peoples');
                                 if($context == 'view' && isset($peoples) && !count($peoples) && $record->peoples){
                                     $peoples = $record->peoples->pluck('id')->toArray();
                                 }

                                 if($context == 'view' && !count($peoples)){
                                     return false;
                                 }
                                 return true;
                             })
                            ->afterStateUpdated(function($state, Get $get, Set $set){
                                if($get('tipo_entrada') == 1){
                                    Owner::whereIn('id',$state)->get()->each(function($owner) use ($set){
                                        $lotes = collect($owner->lotes);
                                        // $lotesS = [];
                                        $lote = $lotes->map(function($lote){
                                            return $lote->getNombre();
                                        })->first();

                                        // dd($lote );
                                        $set('lote_ids',$lote);
                                        if($owner->owner_status_id == 2){
                                            Notification::make()
                                                ->title('Propietario moroso: '.$owner->nombres())
                                                ->warning()
                                                ->send();
                                        }
                                    });

                                }
                                if($get('tipo_entrada') == 2){
                                    Employee::whereIn('id',$state)->get()->each(function($employee) use ($set){
                                       if($employee->isVencidoSeguro()){
                                            Notification::make()
                                                ->title('Empleado con seguro vencido: '.$employee->nombres())
                                                ->warning()
                                                ->send();
                                       }
                                    });

                                }
                            })
                            ->live(),
                        /**
                         * FAMILIARES
                         */
                        Forms\Components\CheckboxList::make('families')
                            ->label(__('general.Familiares'))
                            ->searchable()
                            ->options(function(Get $get, $context){

                                // dd($get('families'),$get('peoples'), $context);

                                if(!$get('num_search') && $context != 'view'){
                                    return [];
                                }

                                if($context == 'view' && !count($get('families'))){
                                    return [];
                                }

                                $owner_id = 0;
                                if($get('peoples')){
                                    $owners = $get('peoples');
                                    $owner_id = $owners[0];
                                }

                                return self::searchOwnerFamily($get('num_search'), 'option' , $context == 'view' ? $get('families') : [], $context == 'view' ? 0 : $owner_id );

                            })
                            ->descriptions(function(Get $get , $context){
                                if(!$get('num_search') && $context != 'view'){
                                    return [];
                                }
                                if($context == 'view' && !count($get('families'))){
                                    return [];
                                }
                                $owner_id = 0;
                                if($get('peoples')){
                                    $owners = $get('peoples');
                                    $owner_id = $owners[0];
                                }
                                return self::searchOwnerFamily($get('num_search'), 'descriptions' , $context == 'view' ? $get('families') : [],$owner_id );

                            })
                            ->visible(function(Get $get, $context ){
                                if($get('tipo_entrada') != 1){
                                    return false;
                                }
                                if($context == 'view' && !count($get('families'))){
                                    return false;
                                }
                                return true;
                            })
                            ->afterStateUpdated(function($state, Get $get, Set $set){
                                if($get('tipo_entrada') == 1){
                                    OwnerFamily::whereIn('id',$state)->get()->each(function($family) use ($set){
                                        // dd($family->familiarPrincipal->lotes);
                                        $lotes = collect($family->familiarPrincipal->lotes);

                                        $lote = $lotes->map(function($lote){
                                            return $lote->getNombre();
                                        })->first();

                                        // dd($lote );
                                        $set('lote_ids',$lote);

                                    });

                                }
                            })
                            ->live(),
                    ])
                    ->visible(function(Get $get){
                        //&& ($get('peoples') || $get('families'))
                        return $get('tipo_entrada')  ? true: false;
                    }),

                /**
                 * VISITANTES ESPONTANEOS
                 */
                Forms\Components\Fieldset::make('spontaneous_visit_list')->label('Visita espontánea')
                    ->schema([

                        Forms\Components\CheckboxList::make('spontaneous_visit')->label(__('general.Select one or more options'))
                            ->options(function(Get $get, $context, $record){



                                if($context == 'view'){
                                    $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                }else{
                                    if($get('type') == 2){
                                        $visitantes = OwnerSpontaneousVisit::Dni($get('num_search'))
                                        ->where('aprobado',1)
                                        ->where('agregado',1)
                                        ->where('salida',null)
                                        ->get();
                                    }else{

                                        $visitantes = OwnerSpontaneousVisit::Dni($get('num_search'))
                                        ->where('agregado',null)
                                        ->whereDate('created_at',now())
                                        ->get();
                                    }
                                }

                                $visitantes = $visitantes->map(function($visitante){
                                    $visitante['texto'] = $visitante['first_name'] ." ".$visitante['last_name'] ;
                                    return $visitante;
                                });

                                return $visitantes->pluck('texto','id');
                            })
                            ->descriptions(function(Get $get, $context){
                                if($context == 'view'){
                                    $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                }else{
                                    $visitantes = OwnerSpontaneousVisit::Dni($get('num_search'))
                                                    ->where('agregado',null)
                                                    ->whereDate('created_at',now())
                                                    ->get();
                                }

                                $visitantes = $visitantes->map(function($visitante){
                                    // $visitante->owner->nombres();
                                    $visitante['texto'] = $visitante['dni']. " Relacionado con: " .$visitante->owner->nombres();
                                    return $visitante;
                                });

                                return $visitantes->pluck('texto','id');
                            })
                            ->live(),



                        Actions::make([
                            Action::make('add_spontaneous_visit')
                                ->label('Agregar Visitante espontáneo')
                                ->icon('heroicon-m-plus')
                                // ->requiresConfirmation()
                                ->fillForm(function(Get $get){
                                    $owner = $get('peoples');
                                    if(!count($owner)){
                                        return [];
                                    }
                                    return [
                                        'owner_id' => $owner[0]
                                    ];
                                })
                                ->form([
                                    // Forms\Components\Hidden::make('owner_id'),
                                    Forms\Components\Select::make('owner_id')
                                        ->label(__("general.Owner"))
                                        ->required()
                                        // ->relationship(name: 'owner', modifyQueryUsing: fn (Builder $query) => $query->orderBy('first_name')->orderBy('last_name'))
                                        ->options(Owner::orderBy('first_name')->orderBy('last_name')->get()->map(function($owner){
                                            $owner['name'] = "{$owner->first_name} {$owner->last_name}";
                                            return $owner;
                                        })->pluck("name","id")->toArray())
                                        // ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                                        ->searchable()
                                        // ->afterStateUpdated(function(Set $set, $state){
                                        //     $owner = Owner::find($state);
                                        //     if(!$owner){
                                        //         return ;
                                        //     }
                                        //     $set('name', $owner->first_name . ' ' . $owner->last_name);
                                        //     $set('roles',[3]);
                                        //     $set('email', $owner->email);
                                        // })
                                        ->live(),
                                    Forms\Components\Repeater::make('spontaneous_visit')
                                        ->label('Visitante espontáneo')
                                        ->schema([
                                            Forms\Components\TextInput::make('dni')->required(),
                                            Forms\Components\TextInput::make('first_name')->required(),
                                            Forms\Components\TextInput::make('last_name')->required(),
                                            Forms\Components\TextInput::make('email')->required(),
                                            Forms\Components\TextInput::make('phone')->numeric()->required(),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ])
                                ->visible(function($context){
                                    return $context != 'view' ? true : false;
                                })
                                ->action(function (array $data, $record, Get $get) {

                                    self::createSpontaneusVisit($data);
                                })
                                ,

                        ]),

                    ])
                    ->columns(1)
                    ->visible(function(Get $get, $context){
                        if($context == 'view' && !count($get('spontaneous_visit'))){
                            return false;
                        }
                        return ($get('tipo_entrada') == 1 )? true : false;
                    }),

                /**
                 * LISTA DE AUTOS
                 */
                Forms\Components\Fieldset::make('autos_list')->label('Autos')
                    ->schema([

                        /**
                         * AUTOS LISTADO
                         */
                        Forms\Components\CheckboxList::make('autos')->label(__('general.Select one or more options'))
                            // ->relationship(titleAttribute: 'activities_auto_id')
                            ->searchable()
                            ->options(function(Get $get, $context){

                                if($context == 'view'){
                                    return self::searchAutos($get('autos'),'option');
                                }

                                $data = [];

                                if($get('tipo_entrada') == 1){
                                    $data = count($get('peoples')) ? self::searchOwnersAutos($get('peoples'), 'option') : [];
                                }
                                if($get('tipo_entrada') == 2){
                                    $data = count($get('peoples')) ? self::searchEmployeeAutos($get('peoples'), 'option') : [];
                                }
                                if($get('tipo_entrada') == 3){
                                    
                                    $data = $get('form_control_id') ? self::searchFormAutos($get('form_control_id'), 'option') : [];
                                }

                                if($get('families')){
                                    $datas = $get('families') ? self::searchAutosModel($get('families'),'option','OwnerFamily') : [];
                                    $data = collect($data)->union($datas)->toArray();
								}

                                if($get('spontaneous_visit')){
                                    $datas = $get('spontaneous_visit') ? self::searchAutosModel($get('spontaneous_visit'),'option','OwnerSpontaneousVisit') : [];
                                    $data = collect($data)->union($datas)->toArray();
                                }

                                return $data;
                            })
                            ->descriptions(function(Get $get, $context){

                                if($context == 'view'){
                                    return self::searchAutos($get('autos'),'descriptions');
                                }

                                $data = [];
                                if($get('tipo_entrada') == 1){
                                    $data = count($get('peoples')) ? self::searchOwnersAutos($get('peoples'), 'descriptions') : [];
                                }
                                if($get('tipo_entrada') == 2){
                                    $data = count($get('peoples')) ? self::searchEmployeeAutos($get('peoples'), 'descriptions') : [];
                                }
                                if($get('tipo_entrada') == 3){
                                    $data = $get('form_control_id') ? self::searchFormAutos($get('form_control_id'), 'descriptions') : [];
                                }
                                if($get('families')){
                                    $datas = $get('families') ? self::searchAutosModel($get('families'),'descriptions','OwnerFamily') : [];
                                    $data = collect($data)->union($datas)->toArray();
								}

                                if($get('spontaneous_visit')){
                                    $datas = $get('spontaneous_visit') ? self::searchAutosModel($get('spontaneous_visit'),'descriptions','OwnerSpontaneousVisit') : [];
                                    $data = collect($data)->union($datas)->toArray();
                                }
                                return $data;
                            }),
                        /**
                         * ACCIONES
                         */
                        Actions::make([
                            Action::make('add_auto')
                                ->label('Agregar Auto')
                                ->icon('heroicon-m-plus')
                                // ->requiresConfirmation()
                                ->fillForm(function(Get $get){
                                    $model = '';
                                    if($get('tipo_entrada') == 1){
                                        $model = 'Owner';

                                    }else if ($get('tipo_entrada') == 2){
                                        $model = 'Employee';
                                    }else if($get('tipo_entrada') == 3){
                                        $model = 'FormControl';
                                    }
                                  
                                    return [
                                        'model' => $model,
                                        'tipo_entrada' => $get('tipo_entrada'),
                                        'num_search' => $get('num_search'),
                                        'families' => $get('families'),
                                        'peoples' => $get('peoples'),
                                        'form_control_id' => $get('form_control_id')
                                    ];
                                })
                                ->form([
                                    Forms\Components\Hidden::make('model'),
                                    Forms\Components\Hidden::make('tipo_entrada'),
                                    Forms\Components\Hidden::make('num_search'),
                                    Forms\Components\Repeater::make('autos')
                                        ->schema([
                                            Forms\Components\TextInput::make('marca')->required(),
                                            Forms\Components\TextInput::make('modelo')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('patente')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('color'),
                                            Forms\Components\TextInput::make('color')->default(function(Get $get){
                                                return $get('../../form_control_id');
                                            }),

                                            // Forms\Components\Radio::make('model_id')
                                            //     ->columnSpanFull()
                                            //     ->label(__('general.Select the responsible person'))
                                            //     ->afterStateUpdated(function($state, Set $set){
                                            //         // $set('model','Owner');
                                            //         $set('model_id',$state);
                                            //     })
                                            //     ->options(function(Get $get , $context){

                                            //         $data = self::getPeoples([
                                            //             'tipo_entrada' =>  $get('../../tipo_entrada'),
                                            //             'num_search' => $get('../../num_search'),
                                            //             'form_control_id' => $get('../../form_control_id') ?? null,
                                            //             'tipo' => 'option',
                                            //             'ids' =>  $get('../../peoples'),
                                            //         ]);


                                            //         // dd($data, $visitantes);
                                            //         return $data;
                                            //     })
                                            //     ->descriptions(function(Get $get, $context){

                                            //         $data = self::getPeoples([
                                            //             'tipo_entrada' =>  $get('../../tipo_entrada'),
                                            //             'num_search' => $get('../../num_search'),
                                            //             'tipo' => 'descriptions',
                                            //             'form_control_id' => $get('../../form_control_id') ?? null,
                                            //             'ids' =>   $get('../../peoples'),
                                            //         ]);

                                            //         return $data;

                                            //     }),

                                            Forms\Components\Radio::make('familiar_model_id')->label('')
                                                ->reactive()
                                                ->columnSpanFull()
                                                ->afterStateUpdated(function($state, Set $set){
                                                    $set('model','OwnerFamily');
                                                    $set('model_id',$state);
                                                })
                                                ->options(function(Get $get , $context){
                                                        $type = 'option';
                                                        $ids = $get('../../families');
                                                        $mapeo = function($people) use ($type){

                                                            if($type == 'option'){
                                                                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                                                                $people['texto'].= ' - ' .$people['parentage'] ;
                                                            }else{
                                                                // dd($people->familiarPrincipal);
                                                                $people['texto'] = $people->dni;
                                                                $people['texto'] .= ' - Familiar de: '.$people->familiarPrincipal->first_name . " " . $people->familiarPrincipal->last_name ;
                                                            }

                                                            return $people;
                                                        };
                                                        $data = OwnerFamily::whereIn('id', $ids)->get();

                                                    return $data->map($mapeo)->pluck('texto','id')->toArray();
                                                })
                                                ->descriptions(descriptions: function(Get $get, $context){

                                                        $type = 'descriptions';
                                                        $ids = $get('../../families');
                                                        $mapeo = function($people) use ($type){

                                                            if($type == 'option'){
                                                                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                                                                $people['texto'].= ' - ' .$people['parentage'] ;
                                                            }else{
                                                                // dd($people->familiarPrincipal);
                                                                $people['texto'] = $people->dni;
                                                                $people['texto'] .= ' - Familiar de: '.$people->familiarPrincipal->first_name . " " . $people->familiarPrincipal->last_name ;
                                                            }

                                                            return $people;
                                                        };
                                                        $data = OwnerFamily::whereIn('id', $ids)->get();

                                                    return $data->map($mapeo)->pluck('texto','id')->toArray();

                                                })
                                                ->visible(function(Get $get){
                                                    return $get('../../families') && count($get('../../families')) ? true : false;
                                                }),
                                                // ALTER TABLE `autos` CHANGE `model` `model` ENUM('Employee','Owner','FormControl','OwnerFamily','OwnerSpontaneousVisit') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
                                            Forms\Components\Radio::make('espontaneo_model_id')->label('')
                                                ->reactive()
                                                ->columnSpanFull()
                                                ->afterStateUpdated(function($state, Set $set){
                                                    $set('model','OwnerSpontaneousVisit');
                                                    $set('model_id',$state);
                                                })
                                                ->options(function(Get $get , $context){
                                                    return  OwnerSpontaneousVisit::Dni( $get('../../num_search'))
                                                        ->where('agregado',null)
                                                        ->whereDate('created_at',now())
                                                        ->get()->map(function($visitante){
                                                            $visitante['texto'] = $visitante['first_name'] ." ".$visitante['last_name'] ;
                                                            return $visitante;
                                                        })->pluck('texto','id')->toArray();
                                                })
                                                ->descriptions(descriptions: function(Get $get, $context){
                                                    return  OwnerSpontaneousVisit::Dni( $get('../../num_search'))
                                                        ->where('agregado',null)
                                                        ->whereDate('created_at',now())
                                                        ->get()->map(function($visitante){
                                                            $visitante['texto'] = $visitante['dni']. " Relacionado con: " .$visitante->owner->nombres();
                                                            return $visitante;
                                                        })->pluck('texto','id')->toArray();
                                                })
                                                // ->visible(function(Get $get){
                                                //     return $get('../../spontaneous_visit') && count($get('../../spontaneous_visit')) ? true : false;
                                                // })
                                                ,


                                            Forms\Components\Hidden::make('model')->default(function(Get $get){
                                                return $get('../../model');
                                            }),
                                        ])
                                        ->columns(4)->columnSpanFull(),
                                ])
                                ->visible(function($context){
                                    return $context != 'view' ? true : false;
                                })
                                ->action(function (array $data, $record, Get $get) {
                                    $autos = collect($data['autos']);
                                    // dd($autos );
                                    self::createAuto($autos->toArray(), ['type' => $get('tipo_entrada')] );
                                }),

                        ]),
                    ])
                    ->columns(1)
                    ->visible(function(Get $get, $context){
                        if($context == 'view' && !count($get('autos'))){
                            return false;
                        }
                        return $get('tipo_entrada') ? true: false;
                    }),


                /**
                 * LOTE
                 */
                Forms\Components\Select::make('lote_ids')
                        ->required(function(Get $get){
                            return ($get('type') == 1 && ($get('tipo_entrada') == 1 ))? true : false;
                        })
                        ->label(__('general.SelectedLote'))
                        ->options(function(Get $get){
                            return Lote::get()->map(function($lote){
                                $lote['lote_name'] = $lote->getNombre();
                                return $lote;
                            })->pluck('lote_name', 'lote_name')->toArray();
                        })
                        ->searchable()
                        ->visible(function(Get $get, $context){
                            return $context == 'view' && !$get('lote_ids') ? false:true;
                        })
                        ->visible(function(Get $get, $context){
                            if($context == 'view' && !count($get('spontaneous_visit'))){
                                return false;
                            }
                            return ($get('tipo_entrada') == 1 || $get('tipo_entrada') == 2 )? true : false;
                        }),
                /**
                 * /////
                 */
                Forms\Components\TextInput::make('observations')
                    ->columnSpanFull()
                    ->label(__('general.Observations'))
                    ->visible(function(Get $get){
                        return $get('tipo_entrada') ? true: false;
                    }),

                Forms\Components\Toggle::make('is_force')
                    ->label('Forzar entrada a empleado (según horario configurado)')
                    ->visible(function(Get $get, $context){
                        return $get('tipo_entrada') == 2 && $context == 'create' ? true: false;
                    }),

                Actions::make([
                        Action::make('create')
                            ->label('Crear nueva Entrada/Salida')
                            ->icon('heroicon-m-plus')
                            ->url(fn (): string => route('filament.admin.resources.activities.create'))
                            ,

                    ])->visible(function($context){
                        return $context == 'view';
                    }),
            ])->columns(1);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at','desc'))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Entry' => __('general.Entry'),
                        'Exit' => __('general.Exit'),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Entry' => 'success',
                        'Exit' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('tipo_entrada')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                         '1' => 'Propietarios',
                         '2' => 'Empleados',
                         '3' => 'Otros'
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray',
                        '2' => 'success',
                        '3' => 'warning'
                    }),
                Tables\Columns\TextColumn::make('lote_ids')
                    ->label(__('general.Lotes'))
                    ->numeric()
                    ->sortable()
                    // ->visible(function(Get $get){
                    //     return $get('tipo_entrada') != 1 ? true: false;
                    // })
                    ,

                    // Tables\Columns\TextColumn::make('lote_ids')
                    // ->label(__('general.Lotes'))
                    // ->numeric()
                    // ->sortable()
                    // ->visible(function(Get $get){
                    //     return $get('tipo_entrada') == 1 ? true: false;
                    // })
                    // ,
                Tables\Columns\TextColumn::make('formControl.access_type')
                    ->badge()
                    ->label(__("general.TypeActivitie"))
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
                    }),
                Tables\Columns\TextColumn::make('formControl.income_type')
                    ->badge()
                    ->label(__("general.TypeIncome"))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Inquilino' => 'Inquilino',
                        'Trabajador' => 'Trabajador',
                        'Visita' => 'Visita'
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Inquilino' => 'success',
                        'Trabajador' => 'gray',
                        'Visita' => 'warning'
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([

                Filter::make('created_at')
                ->label('Fecha de creación')
				->form([
                    Forms\Components\DatePicker::make('created_at'),
                ])->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_at'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '=', $date),
                        );
                }),
                SelectFilter::make('type')
                ->label('Tipo')
                ->options([
                    'Entry' => __('general.Entry'),
                                    'Exit' => __('general.Exit'),
                ]),

                Filter::make('buscar')
                    ->label(__('Buscar'))
                    ->form([
                        Forms\Components\TextInput::make('query')
                            ->label(__('general.Search'))
                            ->placeholder('Buscar por nombre o dni'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['query'])) {
                            // Obtén todos los modelos dentro del namespace App\Models
							 $d = ActivitiesPeople::limit(100)->get();
                                $d = $d->groupBy('model')->keys();
								$models = $d->map(function($model){
									if($model == 'FormControl'){
										$model = 'FormControlPeople';
									}
									return "App\Models\\".$model;
								});
                                //dd($d);
                            // Construye la consulta para buscar en `peoples`
                            $query->whereHas('peoples', function ($peopleQuery) use ($models, $data) {
								 //dd($peopleQuery,$models, $data);
                                foreach ($models as $modelClass) {
                                    $peopleQuery->orWhere('model', class_basename($modelClass))
                                                ->whereIn('model_id', $modelClass::where(function ($query) use ($data) {
                                                    $query->where('dni', 'like', "%{$data['query']}%")
                                                          ->orWhere('first_name', 'like', "%{$data['query']}%")
                                                          ->orWhere('last_name', 'like', "%{$data['query']}%");
                                                })->pluck('id'));
                                }
                            });
                        }
                    })
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ManageActivities::route('/'),
            'create' => Pages\ActivitiesPage::route('/create'),
            'view' => Pages\ViewActivitie::route('/{record}'),
            // 'edit' => Pages\ActivitiesPageEdit::route('/{record}/edit'),

        ];
    }
}
