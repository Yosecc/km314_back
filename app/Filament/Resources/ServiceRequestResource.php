<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Filament\Resources\ServiceRequestResource\RelationManagers;
use App\Models\CommonSpaces;
use App\Models\HomeInspection;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\Property;
use App\Models\RentalAttention;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestFile;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\StartUp;
use App\Models\StartUpOption;
use App\Models\User;
use App\Models\WorksAndInstallation;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $label = 'solicitud';
    // protected static ?string $navigationGroup = 'Solicitudes';

    public static $service = null;

    public static function getPluralModelLabel(): string
    {
        return 'Solicitudes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Grid::make()
                    ->schema([

                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        Forms\Components\Select::make('service_id')
                            ->label('Servicio')
                            ->required()
                            ->relationship(name: 'service', titleAttribute: 'name')
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                self::$service = Service::find($state);
                                $set('name',self::$service->name);
                                $set('model',self::$service->model);
                                $set('service_request_type_id',self::$service->service_request_type_id);
                            }),

                        Forms\Components\Select::make('service_request_type_id')
                            ->label('Tipo de Solicitud (Uso interno)')
                            ->required()
                            ->relationship(name: 'serviceRequestType', titleAttribute: 'name')
                            ->live(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->live()
                            ->maxLength(255)->columnSpan(2),

                        Forms\Components\Hidden::make('model'),

                        Forms\Components\Select::make('model_id')
                            ->label('')
                            ->required()
                            ->options(RentalAttention::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'RentalAttention' )
                            ->visible( fn (Get $get) => $get('model') == 'RentalAttention' ),

                        Forms\Components\Select::make('model_id')
                            // ->label(__("general.LoteStatus"))
                            ->label('')
                            ->required()
                            ->options(HomeInspection::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'HomeInspection' )
                            ->visible( fn (Get $get) => $get('model') == 'HomeInspection' ),

                        Forms\Components\Select::make('model_id')
                            // ->label(__("general.LoteStatus"))
                            ->label('')
                            ->required()
                            ->options(WorksAndInstallation::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'WorksAndInstallation' )
                            ->visible( fn (Get $get) => $get('model') == 'WorksAndInstallation' ),

                        Forms\Components\Select::make('model_id')
                            // ->label(__("general.LoteStatus"))
                            ->label('Espacio')
                            ->required()
                            ->options(CommonSpaces::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'CommonSpaces' )
                            ->visible( fn (Get $get) => $get('model') == 'CommonSpaces' ),

                        Forms\Components\Select::make('model_id')
                            // ->label(__("general.LoteStatus"))
                            ->label('')
                            ->required()
                            ->options(StartUp::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'StartUp' )
                            ->visible( fn (Get $get) => $get('model') == 'StartUp' ),

                        Select::make('options')
                            ->label('opciones')
                            ->multiple()
                            ->searchable()
                            ->options(StartUpOption::get()->pluck('name','id')->toArray())
                            ->disabled( fn (Get $get) => $get('model') != 'StartUp' )
                            ->visible( fn (Get $get) => $get('model') == 'StartUp' ),


                    ])->columns(2),

                Forms\Components\TextInput::make('observations')->label('Observaciones'),
                Fieldset::make('responsible')
                    ->label('Responsable')
                    ->relationship('responsible')
                    ->schema([
                        Forms\Components\TextInput::make('dni')
                            ->label(__("general.DNI"))
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('first_name')
                            ->label(__("general.FirstName"))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label(__("general.LastName"))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__("general.Phone"))
                            ->tel()
                            ->numeric(),
                    ])
                    ->disabled( fn (Get $get) => $get('model') != 'CommonSpaces' )
                    ->visible( fn (Get $get) => $get('model') == 'CommonSpaces' ),

                    Grid::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Fecha de inicio')
                            ->required()
                            //->minDate(now())
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
                            }),

                        Forms\Components\DateTimePicker::make('ends_at')->label('Fecha de fin')->live(),



            Forms\Components\Select::make('owner_id')->label(__("general.Owner"))
                ->relationship(name: 'owner')
                ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}"),


            Forms\Components\Select::make('lote_id')
                ->label("Lote")
                ->options(Lote::get()->map(function($lote){
                    $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                    return $lote;
                })
                ->pluck('lote_name', 'id')->toArray()),

            // Forms\Components\Select::make('propertie_id')
            //     ->label(__("general.Propertie"))
            //     ->options(Property::get()->pluck('identificador', 'id')->toArray()),

            Forms\Components\Select::make('service_request_status_id')
                ->label("Estado de la Solicitud")
                ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name')
                // ->options(ServiceRequestStatus::get()->pluck('name','id')->toArray())
                ->required(),

            Forms\Components\Select::make('asignado_status_id')
                ->label("Usuario asignado (Uso interno)")
                ->searchable()
                // ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name')
                ->options(User::get()->pluck('name','id')->toArray())
                ,



            Repeater::make('serviceRequestNote')
                ->relationship()
                ->label('Nota')
				->mutateRelationshipDataBeforeFillUsing(function ($record, $data) {
					//dd($record->serviceRequestNote, $data);
					$id = $data['id'];
					$nota = $record->serviceRequestNote()->where('id',$id)->first();
					//Auth::user()->name

                    $data['user_id'] = $nota->user->id;
					$data['name'] = $nota->user->name;
                    return $data;
                })
                ->schema([

                    Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
					Forms\Components\TextInput::make('name')->label('Usuario')->default(Auth::user()->name),
                    Forms\Components\TextInput::make('description')->label('Descripción'),

                ])
                ->defaultItems(0)
                ->columns(1),



            Repeater::make('serviceRequestFile')
                ->relationship()
                ->label('Documentos')
                ->schema([
                    Hidden::make('user_id')->default(Auth::user()->id),
                    Forms\Components\TextInput::make('description')->label('Descripción'),
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo')
                        ->storeFileNamesIn('attachment_file_names'),
                    Actions::make([
                        Action::make('open_file')
                            ->label('Abrir archivo')
                            ->icon('heroicon-m-eye')
                            ->url(function ($record, $context) {
                                return Storage::url($record->file);
                             })
                            ->openUrlInNewTab(),
                    ])
                    ->visible(function($record){
                        return $record ? true : false;
                    }),
                ])
                ->defaultItems(0)
                ->columns(1)
])->columns(2),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\ColorColumn::make('serviceRequestStatus.color')
                ->label(''),
                Tables\Columns\TextColumn::make('serviceRequestStatus.name')->label('Estado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lote')
                    ->formatStateUsing(fn (Lote $state): string => $state->getNombre())
                    ->badge()
                    ->label('Lote'),
                Tables\Columns\TextColumn::make('serviceRequestType.name')->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')->label('Servicio')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')->label('Fecha de inicio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ends_at')->label('Fecha de fin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
