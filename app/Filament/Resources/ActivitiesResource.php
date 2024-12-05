<?php

namespace App\Filament\Resources;

use App\Actions\ResetStars;
use App\Actions\Star;
use App\Filament\Resources\ActivitiesResource\Pages;
use App\Filament\Resources\ActivitiesResource\RelationManagers;
use App\Models\Activities;
use App\Models\Auto;
use App\Models\Employee;
use App\Models\EmployeeAutos;
use App\Models\FormControl;
use App\Models\FormControlAuto;
use App\Models\FormControlPeople;
use App\Models\Lote;
use App\Models\OwnerSpontaneousVisit;
use App\Models\Owner;
use App\Models\OwnerAutos;
use App\Models\OwnerFamily;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Radio;


class ActivitiesResource extends Resource
{
    protected static ?string $model = Activities::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationLabel = 'Actividades';

    protected static ?string $label = 'actividad';

    protected static ?string $navigationGroup = 'Control de acceso';

    protected static $PARAMS = null;

    public static function getPluralModelLabel(): string
    {
        return 'actividades';
    }

    public static function searchEmployee($dni, $type, $ids = [])
    {
        $data = Employee::where('dni', 'like', '%'.$dni.'%')->limit(10)->get();

        $mapeo = function($people) use ($type){

            if($type == 'option'){
                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                $people['texto'].= ' - '.__('general.Employee');
            }else{
                $people['texto'] = $people->dni;
                $people['texto'] .= ' - '.$people->work->name;
            }

            return $people;
        };

        if(count($ids)){
            $data = Employee::whereIn('id', $ids)->get()->map($mapeo);
        }

        $data = $data->map($mapeo);

        return $data->pluck('texto','id')->toArray();
    }

    public static function searchOwners($dni, $type , $ids = [])
    {
        $data = Owner::where('dni', 'like', '%'.$dni.'%')->limit(10)->get();

        $mapeo = function($people) use ($type){

            if($type == 'option'){
                $people['texto'] = $people['first_name']. ' '.$people['last_name'];
                // $people['texto'].= ' - Propietario';
            }else{
                $people['texto'] = $people->dni;
                $people['texto'] .= ' - Propietario';
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
        $data = OwnerFamily::where('dni','like','%'.$dni.'%')->limit(10)->get();

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

        if(count($ids)){
            $data = OwnerFamily::whereIn('id', $ids)->get()->map($mapeo);
        }

        if($owner_id!=0){
            $data = OwnerFamily::where('owner_id', $owner_id)->get()->map($mapeo);
        }

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


    public static function createAuto($data, $config)
    {
        $data = collect($data)->map(function($auto) use ($config){
            $auto['user_id'] = Auth::user()->id;
            $auto['created_at'] = Carbon::now();
            $auto['updated_at'] = Carbon::now();
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
            ];
        });

        OwnerSpontaneousVisit::insert($visitantes->toArray());

    }

    public static function getPeoples($data)
    {
        self::$PARAMS = [
            'num_search' => $data['num_search'],
            'tipo_entrada' => $data['tipo_entrada']
        ];

        if( $data['tipo_entrada'] == 2){
            return $data['num_search'] || count($data['ids']) ? self::searchEmployee($data['num_search'], $data['tipo'], $data['ids']) : [];
        }

        if( $data['tipo_entrada'] == 1){
            return $data['num_search'] || count($data['ids']) ? self::searchOwners($data['num_search'], $data['tipo'], $data['ids']) : [];
        }

        if( $data['tipo_entrada'] == 3 && $data['form_control_id']){
            return $data['num_search'] || count($data['ids']) ? self::searchFormControl($data['form_control_id'],  $data['tipo'], $data['ids']) : [];
        }

        return [];
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('type')
                    ->required()
                    ->columnStart(4)
                    ->options([
                        'Entry' => __('general.Entry'),
                        'Exit' => __('general.Exit'),
                    ])
                    ->label(__('general.Type'))
                    ->default(isset($_GET['type']) ? $_GET['type']  : '' ),

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

                Forms\Components\Fieldset::make('search')->label(__('general.Search'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('num_search')->label(__('general.DNI'))->live(),
                            ]),
                    ])
                    ->visible(function(Get $get, $context){
                        return $get('tipo_entrada') && $context != 'view' ? true: false;
                    })
                    ->columns(1),
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
                                        return FormControl::whereHas('peoples', function ($query) use($num){
                                            $query->where('dni','like','%'.$num.'%');
                                        })->orderBy('id','desc')->get()->map(callback: $mapeo)
                                        // ->whereNotIn('status',['Vencido','Expirado'])
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
                                        return FormControl::whereHas('peoples', function ($query) use($num){
                                            $query->where('dni','like','%'.$num.'%');
                                        })->orderBy('id','desc')->get()->map($mapeo)
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

                Forms\Components\Fieldset::make('peoples_list')->label(__('general.Peoples'))
                    ->columns(2)
                    ->schema([

                        Forms\Components\CheckboxList::make('peoples')->label(__('general.Select people one or more options'))
                            ->searchable()
                            ->options(function(Get $get, $context, $record){
                                // dd($get('peoples'), $get('tipo_entrada'), $record);
                                return self::getPeoples([
                                    'tipo_entrada' => $get('tipo_entrada'),
                                    'num_search' => $get('num_search') ,
                                    'form_control_id' => $get('form_control_id'),
                                    'tipo' => 'option',
                                    'ids' =>  $context == 'view' ? $get('peoples') : [],
                                    'context' => $context
                                ]);
                            })
                            ->descriptions(function(Get $get , $context){
                                return self::getPeoples([
                                    'tipo_entrada' => $get('tipo_entrada'),
                                    'num_search' => $get('num_search') ,
                                    'form_control_id' => $get('form_control_id'),
                                    'tipo' => 'descriptions',
                                    'ids' =>  $context == 'view' ? $get('peoples') : [],
                                    'context' => $context
                                ]);
                            })
                            ->visible(function(Get $get, $context ){
                                if($context == 'view' && !count($get('peoples'))){
                                    return false;
                                }
                                return true;
                            })
                            ->live(),

                        Forms\Components\CheckboxList::make('families')->label(__('general.Familiares'))
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
                            ->live(),
                    ])
                    ->visible(function(Get $get){
                        return $get('tipo_entrada') ? true: false;
                    }),


                Forms\Components\Fieldset::make('autos_list')->label('Autos')
                    ->schema([

                        Forms\Components\CheckboxList::make('autos')->label(__('general.Select one or more options'))
                            // ->relationship(titleAttribute: 'activities_auto_id')
                            ->searchable()
                            ->options(function(Get $get, $context){

                                if($context == 'view'){
                                    return self::searchAutos($get('autos'),'option');
                                }

                                if($get('tipo_entrada') == 1){
                                    return count($get('peoples')) ? self::searchOwnersAutos($get('peoples'), 'option') : [];
                                }
                                if($get('tipo_entrada') == 2){
                                    return count($get('peoples')) ? self::searchEmployeeAutos($get('peoples'), 'option') : [];
                                }
                                if($get('tipo_entrada') == 3){
                                    return $get('form_control_id') ? self::searchFormAutos($get('form_control_id'), 'option') : [];
                                }
                                return [];
                            })
                            ->descriptions(function(Get $get, $context){

                                if($context == 'view'){
                                    return self::searchAutos($get('autos'),'descriptions');
                                }

                                if($get('tipo_entrada') == 1){
                                    return count($get('peoples')) ? self::searchOwnersAutos($get('peoples'), 'descriptions') : [];
                                }
                                if($get('tipo_entrada') == 2){
                                    return count($get('peoples')) ? self::searchEmployeeAutos($get('peoples'), 'descriptions') : [];
                                }
                                if($get('tipo_entrada') == 3){
                                    return $get('form_control_id') ? self::searchFormAutos($get('form_control_id'), 'descriptions') : [];
                                }
                                return [];
                            }),
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
                                            'families' => $get('families')
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

                                                Forms\Components\Radio::make('model_id')
                                                    ->label(__('general.Select the responsible person'))
                                                    ->afterStateUpdated(function($state, Set $set){
                                                        $set('model','Owner');
                                                        $set('model_id',$state);
                                                    })
                                                    ->options(function(Get $get , $context){

                                                        $data = self::getPeoples([
                                                            'tipo_entrada' =>  $get('../../tipo_entrada'),
                                                            'num_search' => $get('../../num_search'),
                                                            'tipo' => 'option',
                                                            'ids' =>  [],
                                                        ]);



                                                        return $data;
                                                    })
                                                    ->descriptions(function(Get $get, $context){

                                                        $data = self::getPeoples([
                                                            'tipo_entrada' =>  $get('../../tipo_entrada'),
                                                            'num_search' => $get('../../num_search'),
                                                            'tipo' => 'descriptions',
                                                            'ids' =>   [],
                                                        ]);

                                                        return $data;

                                                    }),

                                                Forms\Components\Radio::make('familiar_model_id')->label('')
                                                    ->reactive()
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

                Forms\Components\Fieldset::make('spontaneous_visit_list')->label('Visita espontánea')
                    ->schema([

                        Forms\Components\CheckboxList::make('spontaneous_visit')->label(__('general.Select one or more options'))
                            ->options(function(Get $get, $context, $record){

                            //    dd( $get('tipo_entrada'),$get('num_search'), $get('type'));

                               if($get('type') == 'Exit' && $get('num_search') && $get('tipo_entrada') == 1){

                                $visitantes = OwnerSpontaneousVisit::where('dni', 'LIKE' ,'%'.$get('num_search').'%')->get();

                                $visitantes = $visitantes->map(function($visitante){
                                    $visitante['texto'] = $visitante['first_name'] ." ".$visitante['last_name'] ;
                                    return $visitante;
                                });

                                return $visitantes->pluck('texto','id');

                               }

                               if($context == 'view' && $get('type') == 'Exit' && count($get('spontaneous_visit')) && $get('tipo_entrada') == 1){
                                $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                $visitantes = $visitantes->map(function($visitante){
                                    $visitante['texto'] = $visitante['first_name'] ." ".$visitante['last_name'] ;
                                    return $visitante;
                                });

                                return $visitantes->pluck('texto','id');
                               }


                                if(!$get('peoples') && $get('tipo_entrada') == 1){
                                    return [];
                                }
                                $owner = $get('peoples');
                                $owner_id = $owner[0];

                                if($context == 'view'){
                                    $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                }else{
                                    $visitantes = OwnerSpontaneousVisit::where('owner_id', $owner_id)->where('agregado',null)->get();
                                }

                                $visitantes = $visitantes->map(function($visitante){
                                    $visitante['texto'] = $visitante['first_name'] ." ".$visitante['last_name'] ;
                                    return $visitante;
                                });

                                return $visitantes->pluck('texto','id');
                            })
                            ->descriptions(function(Get $get, $context){

                                if($get('type') == 'Exit' && $get('num_search') != ''){

                                    $visitantes = OwnerSpontaneousVisit::where('dni', 'LIKE' ,'%'.$get('num_search').'%')->get();

                                    $visitantes = $visitantes->map(function($visitante){
                                        $visitante['texto'] = $visitante['dni'];
                                        return $visitante;
                                    });

                                    return $visitantes->pluck('texto','id');

                                   }

                                   if($context == 'view' && $get('type') == 'Exit' && count($get('spontaneous_visit')) && $get('tipo_entrada') == 1){
                                    $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                    $visitantes = $visitantes->map(function($visitante){
                                        $visitante['texto'] = $visitante['dni'];
                                        return $visitante;
                                    });

                                    return $visitantes->pluck('texto','id');
                                   }


                                if(!$get('peoples') && $get('tipo_entrada') == 1){
                                    return [];
                                }
                                $owner = $get('peoples');
                                $owner_id = $owner[0];
                                if($context == 'view'){
                                    $visitantes = OwnerSpontaneousVisit::whereIn('id', $get('spontaneous_visit'))->get();
                                }else{
                                    $visitantes = OwnerSpontaneousVisit::where('owner_id', $owner_id)->where('agregado',null)->get();
                                }

                                $visitantes = $visitantes->map(function($visitante){
                                    $visitante['texto'] = $visitante['dni'];
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
                                    // dd($get('peoples'), $owner[0]);
                                    return [
                                        'owner_id' => $owner[0]
                                    ];
                                })
                                ->form([
                                    Forms\Components\Hidden::make('owner_id'),
                                    Forms\Components\Repeater::make('spontaneous_visit')
                                        ->label('Visitante espontáneo')
                                        ->schema([
                                            Forms\Components\TextInput::make('dni')->required(),
                                            Forms\Components\TextInput::make('first_name')->required(),
                                            Forms\Components\TextInput::make('last_name')->required(),
                                            Forms\Components\TextInput::make('email'),
                                            Forms\Components\TextInput::make('phone')->numeric(),
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

                Forms\Components\Textarea::make('observations')
                    ->columnSpanFull()
                    ->label(__('general.Observations'))
                    ->visible(function(Get $get){
                        return $get('tipo_entrada') ? true: false;
                    })
            ])->columns(4);
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
                    ->sortable(),
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
                //
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
