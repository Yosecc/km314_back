<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Models\CommonSpaces;

use App\Models\FormControlPeople;
use App\Models\HomeInspection;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\Property;
use App\Models\RentalAttention;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestType;
use App\Models\StartUp;
use App\Models\StartUpOption;
use App\Models\WorksAndInstallation;


use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class CalendarWidget extends FullCalendarWidget
{

    use HasWidgetShield;
    public Model | string | null $model = ServiceRequest::class;
    
    protected static ?string $heading = 'Calendario de Reservas de Servicios';


    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'dayGridWeek,dayGridDay,dayGridMonth',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()->mountUsing(
                function (Form $form, array $arguments) {
                    $form->fill([
                        'starts_at' => $arguments['start'] ?? null,
                        'ends_at' => $arguments['end'] ?? null
                    ]);
                }
            ),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function viewAction() : Action
    {
        return Actions\ViewAction::make();
    }

    public function getFormSchema(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('Service')
                    ->label('Servicio')
                    ->schema([
                        Grid::make()
                        ->schema([
                            Hidden::make('user_id')->default(Auth::user()->id),

                            Select::make('service_request_type_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->relationship(name: 'serviceRequestType', titleAttribute: 'name'),


                            Select::make('service_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->relationship(name: 'service', titleAttribute: 'name')
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set) {
                                    $service = Service::find($state);
                                    $set('name',$service->name);
                                    $set('model',$service->model);
                                }),

                            TextInput::make('name')
                                ->required()
                                ->live()
                                ->maxLength(255)->columnSpan(2),

                            Hidden::make('model'),

                            Select::make('model_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->options(RentalAttention::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'RentalAttention' )
                                ->visible( fn (Get $get) => $get('model') == 'RentalAttention' ),

                            Select::make('model_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->options(HomeInspection::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'HomeInspection' )
                                ->visible( fn (Get $get) => $get('model') == 'HomeInspection' ),

                            Select::make('model_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->options(WorksAndInstallation::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'WorksAndInstallation' )
                                ->visible( fn (Get $get) => $get('model') == 'WorksAndInstallation' ),

                            Select::make('model_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->options(CommonSpaces::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'CommonSpaces' )
                                ->visible( fn (Get $get) => $get('model') == 'CommonSpaces' ),

                            Select::make('model_id')
                                // ->label(__("general.LoteStatus"))
                                ->required()
                                ->options(StartUp::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'StartUp' )
                                ->visible( fn (Get $get) => $get('model') == 'StartUp' ),

                            Select::make('options')
                                ->multiple()
                                ->searchable()
                                ->options(StartUpOption::get()->pluck('name','id')->toArray())
                                ->disabled( fn (Get $get) => $get('model') != 'StartUp' )
                                ->visible( fn (Get $get) => $get('model') == 'StartUp' ),


                        ])->columns(2),
                            Textarea::make('observation'),
                            Fieldset::make('responsible')
                                ->label('Responsable')
                                ->relationship('responsible')
                                ->schema([
                                    TextInput::make('dni')
                                        ->label(__("general.DNI"))
                                        ->required()
                                        ->numeric(),
                                    TextInput::make('first_name')
                                        ->label(__("general.FirstName"))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('last_name')
                                        ->label(__("general.LastName"))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('phone')
                                        ->label(__("general.Phone"))
                                        ->tel()
                                        ->numeric(),
                            ])
                            ->disabled( fn (Get $get) => $get('model') != 'CommonSpaces' )
                            ->visible( fn (Get $get) => $get('model') == 'CommonSpaces' ),
                    ]),
                Wizard\Step::make('Date')
                    ->label('Fecha')
                    ->schema([

                        DateTimePicker::make('starts_at')
                            ->label('Fecha de inicio')
                            ->required()
                            ->minDate(now())
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {

                                //dd($get('service_request_type_id'), );
                                $SRtype = ServiceRequestType::find($get('service_request_type_id'));

                                if(!$SRtype->isCalendar){
                                    return;
                                }
                                // Fecha y hora de inicio seleccionada
                                $selectedStartDateTime = Carbon::parse($state); // Convertir $state a Carbon

                                // Calcular la nueva fecha de fin como una hora después de la fecha de inicio
                                $selectedEndDateTime = $selectedStartDateTime->copy()->addMinutes(60);

                                // Actualizar el campo 'ends_at' siempre que cambie la fecha de inicio
                                $set('ends_at', $selectedEndDateTime->format('Y-m-d H:i:s'));

                                $isAvailable = ServiceRequest::isAvailable(
                                    $selectedStartDateTime->format('Y-m-d H:i:s'),
                                    $selectedEndDateTime->format('Y-m-d H:i:s'),
                                    $get('service_request_type_id'),
                                    $get('model_id'),
                                    $get('model')
                                );

                                if (!$isAvailable) {
                                    Notification::make()
                                        ->title('Fecha de reservación no está disponible')
                                        ->danger()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Fecha de reservación disponible')
                                        ->success()
                                        ->send();
                                }
                            })
                        ,

                        DateTimePicker::make('ends_at')->label('Fecha de fin')->required()->live(),
                    ]),
                Wizard\Step::make('Info')
                    ->label('Información')
                    ->schema([

                        Select::make('owner_id')->label(__("general.Owner"))
                            ->relationship(name: 'owner')
                            ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}"),

                        Select::make('lote_id')
                            ->label(__("general.Lotes"))
                            ->options(Lote::get()->map(function($lote){
                                $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                return $lote;
                            })
                            ->pluck('lote_name', 'id')->toArray()),

                        Select::make('propertie_id')
                            ->label(__("general.Propertie"))
                            ->options(Property::get()->pluck('identificador', 'id')->toArray()),

                        Select::make('service_request_status_id')
                            // ->label(__("general.LoteStatus"))
                            ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name')
                            // ->options(ServiceRequestStatus::get()->pluck('name','id')->toArray())
                            ->required(),
                    ])->columns(2),
            ]),
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        // return ['id'=> 'a','title' => 'My event','start'=> '2024-07-18'];
        $serviceRequest = ServiceRequest::query()
        ->where('starts_at', '>=', $fetchInfo['start'])
        ->where('ends_at', '<=', $fetchInfo['end'])
        ->get()
        ->map(
            fn (ServiceRequest $event) => [
                'title' => $event->name,
                'id' => $event->id,
                'start' => $event->starts_at,
                'end' => $event->ends_at,
                'backgroundColor' => $event->service->color,
                'borderColor' => $event->service->color,
                // 'url' => EventResource::getUrl(name: 'view', parameters: ['record' => $event]),
                // 'shouldOpenUrlInNewTab' => true
            ]
        )
        ->all();

        $formControlPeople = FormControlPeople::query()
            ->whereHas('formControl', function ($query) use ($fetchInfo) {
                $query->where('status', 'Authorized')
                    ->whereHas('dateRanges', function($q) use ($fetchInfo) {
                        $q->where('start_date_range', '<=', $fetchInfo['end'])
                          ->where('end_date_range', '>=', $fetchInfo['start']);
                    });
            })
        ->get()
        ->flatMap(function (FormControlPeople $person) {
            $formControl = $person->formControl;
            if (!$formControl) return [];
            $dateRanges = $formControl->dateRanges()->get();
            $events = collect();
            foreach ($dateRanges as $range) {
                $startDate = Carbon::parse($range->start_date_range);
                $endDate = Carbon::parse($range->end_date_range);
                $current = $startDate->copy();
                while ($current->lte($endDate)) {
                    $start = $current->format('Y-m-d');
                    $end = $current->format('Y-m-d');
                    $horaInicio = null;
                    $horaFin = null;
                    // Si es el primer día y hay hora de inicio, agrégala
                    if ($current->eq($startDate) && !empty($range->start_time_range)) {
                        $start .= ' ' . $range->start_time_range;
                        $horaInicio = $range->start_time_range;
                    }
                    // Si es el último día y hay hora de fin, agrégala
                    if ($current->eq($endDate) && !empty($range->end_time_range)) {
                        $end .= ' ' . $range->end_time_range;
                        $horaFin = $range->end_time_range;
                    }
                    // Determinar color según el turno
                    // Si la hora de inicio está entre 07:00 y 18:00 => verde, si está entre 18:00 y 07:00 => azul
                    $colorFondo = '#c8e6c9'; // verde claro por defecto
                    $colorBorde = '#388e3c'; // verde oscuro por defecto
                    $horaComparar = $horaInicio ?? $horaFin;
                    if ($horaComparar) {
                        $hora = intval(substr($horaComparar, 0, 2));
                        if ($hora >= 18 || $hora < 7) {
                            $colorFondo = '#bbdefb'; // azul claro
                            $colorBorde = '#1976d2'; // azul oscuro
                        }
                    }
                    $events->push([
                        'title' => $person->first_name . ' ' . $person->last_name ,
                        'id' => 'People'.$person->id.'_'.$range->id.'_'.$current->format('Ymd'),
                        'start' => $start,
                        'end' => $end,
                        'backgroundColor' => $colorFondo,
                        'borderColor' => $colorBorde,
                        'url' => route('filament.admin.resources.form-controls.view', $formControl),
                        'shouldOpenUrlInNewTab' => true
                    ]);
                    $current->addDay();
                }
            }
            return $events;
        })
        ->all();

        return array_merge($serviceRequest,$formControlPeople);
    }
}
