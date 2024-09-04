<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Service;
use App\Models\StartUp;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\CommonSpaces;
use App\Models\StartUpOption;
use App\Models\HomeInspection;
use App\Models\ServiceRequest;
use App\Models\RentalAttention;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use App\Models\ServiceRequestStatus;
use App\Models\WorksAndInstallation;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Filament\Resources\ServiceRequestResource\RelationManagers;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $label = 'solicitud';
    protected static ?string $navigationGroup = 'Solicitudes';

    public static $service = null;
    
    public static function getPluralModelLabel(): string
    {
        return 'Solicitudes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Service')
                        ->schema([
                            Grid::make()
                            ->schema([

                                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),

                                Forms\Components\Select::make('service_request_type_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->relationship(name: 'serviceRequestType', titleAttribute: 'name')
                                    ->live(),

                                Forms\Components\Select::make('service_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->relationship(name: 'service', titleAttribute: 'name')
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Set $set) {
                                        self::$service = Service::find($state);
                                        $set('name',self::$service->name);
                                        $set('model',self::$service->model);
                                        $set('service_request_type_id',self::$service->service_request_type_id);
                                    }),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->live()
                                    ->maxLength(255)->columnSpan(2),

                                Forms\Components\Hidden::make('model'),

                                Forms\Components\Select::make('model_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->options(RentalAttention::get()->pluck('name','id')->toArray())
                                    ->disabled( fn (Get $get) => $get('model') != 'RentalAttention' )
                                    ->visible( fn (Get $get) => $get('model') == 'RentalAttention' ),

                                Forms\Components\Select::make('model_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->options(HomeInspection::get()->pluck('name','id')->toArray())
                                    ->disabled( fn (Get $get) => $get('model') != 'HomeInspection' )
                                    ->visible( fn (Get $get) => $get('model') == 'HomeInspection' ),

                                Forms\Components\Select::make('model_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->options(WorksAndInstallation::get()->pluck('name','id')->toArray())
                                    ->disabled( fn (Get $get) => $get('model') != 'WorksAndInstallation' )
                                    ->visible( fn (Get $get) => $get('model') == 'WorksAndInstallation' ),
                                
                                Forms\Components\Select::make('model_id')
                                    // ->label(__("general.LoteStatus"))
                                    ->required()
                                    ->options(CommonSpaces::get()->pluck('name','id')->toArray())
                                    ->disabled( fn (Get $get) => $get('model') != 'CommonSpaces' )
                                    ->visible( fn (Get $get) => $get('model') == 'CommonSpaces' ),
                                
                                Forms\Components\Select::make('model_id')
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
                            Textarea::make('observations'),
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
                        ]),
                    Wizard\Step::make('Date')
                        ->schema([

                            Forms\Components\DateTimePicker::make('starts_at')
                                ->required(),

                            Forms\Components\DateTimePicker::make('ends_at'),
                        ]),
                    Wizard\Step::make('Info')
                        ->schema([

                            Forms\Components\Select::make('owner_id')->label(__("general.Owner"))    
                                ->relationship(name: 'owner')
                                ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}"),

                            Forms\Components\Select::make('lote_id')
                                ->label(__("general.Lotes"))
                                ->options(Lote::get()->map(function($lote){
                                    $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                    return $lote;
                                })
                                ->pluck('lote_name', 'id')->toArray()),

                            // Forms\Components\Select::make('propertie_id')
                            //     ->label(__("general.Propertie"))
                            //     ->options(Property::get()->pluck('identificador', 'id')->toArray()),

                            Forms\Components\Select::make('service_request_status_id')
                                // ->label(__("general.LoteStatus"))
                                ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name')
                                // ->options(ServiceRequestStatus::get()->pluck('name','id')->toArray())
                                ->required(),

                            Repeater::make('serviceRequestNote')
                                ->relationship()
                                ->label('Nota')
                                ->schema([

                                    Hidden::make('user_id')->default(Auth::user()->id),
                                    Textarea::make('description'),

                                ])
                                ->defaultItems(0)
                                ->columns(1),

                            Repeater::make('serviceRequestFile')
                                ->relationship()
                                ->label('Archivo')
                                ->schema([
                                    Hidden::make('user_id')->default(Auth::user()->id),
                                    Textarea::make('description'),
                                    Forms\Components\FileUpload::make('file')
                                ])
                                ->defaultItems(0)
                                ->columns(1)


                        ])->columns(2),
                ]),

                
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                Tables\Columns\ColorColumn::make('serviceRequestStatus.color')
                ->label(''),
                Tables\Columns\TextColumn::make('serviceRequestStatus.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceRequestType.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ends_at')
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
