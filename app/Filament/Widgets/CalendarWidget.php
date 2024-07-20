<?php

namespace App\Filament\Widgets;

use App\Models\Lote;
use App\Models\Owner;

use App\Models\Service;
use App\Models\StartUp;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Property;
use Filament\Forms\Form;
use App\Models\CommonSpaces;
use Filament\Actions\Action;
use App\Models\StartUpOption;
use App\Models\HomeInspection;
use App\Models\ServiceRequest;
use App\Models\RentalAttention;


use Filament\Forms\Components\Grid;
use App\Models\WorksAndInstallation;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\EventResource;
use Filament\Forms\Components\DateTimePicker;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Illuminate\Support\Facades\Auth;

class CalendarWidget extends FullCalendarWidget
{
    
    public Model | string | null $model = ServiceRequest::class;
 
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
                    ->schema([

                        DateTimePicker::make('starts_at')
                            ->required(),

                        DateTimePicker::make('ends_at')
                            ->required(),
                    ]),
                Wizard\Step::make('Info')
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
        return ServiceRequest::query()
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
    }
}