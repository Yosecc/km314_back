<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Lote;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FormControl;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FormControlResource\Pages;
use App\Filament\Resources\FormControlResource\RelationManagers;

class FormControlResource extends Resource
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
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Fieldset::make('')
                    ->schema([
                        Forms\Components\CheckboxList::make('access_type')->label(__("general.TypeActivitie"))
                            ->options(['general' => 'Entrada general', 'playa' => 'Clud playa', 'hause' => 'Club hause', 'lote' => 'Lote', ])
                            ->live()
                            ->columns(2)
                            ->required()
                            ->gridDirection('row'),
                            
                        Forms\Components\Select::make('lote_ids')
                            ->label(__("general.Lotes"))
                            ->multiple()
                            ->options(Lote::get()->map(function($lote){
                                $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                                return $lote;
                            })->pluck('lote_name', 'lote_name')->toArray())
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

                        Forms\Components\CheckboxList::make('income_type')->label(__("general.TypeIncome"))
                            ->options(['Inquilino' => 'Inquilino', 'Trabajador' => 'Trabajador', 'Visita' => 'Visita'])
                            ->columns(2)
                            ->gridDirection('row')
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
                    ])
                    ->columns(2),

                Forms\Components\Fieldset::make('range')->label('Rango de fecha de estancia')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date_range')->label(__('general.start_date_range'))
                            ->minDate(Carbon::now()->format('Y-m-d'))
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
                        Forms\Components\Toggle::make('date_unilimited')->label(__('general.date_unilimited'))->live(),
                    ])
                    ->columns(4),
                Forms\Components\Repeater::make('peoples')
                    ->label(__("general.Peoples"))
                    ->relationship()
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
                        Forms\Components\Toggle::make('is_responsable')->label(__("general.Responsable")),
                        Forms\Components\Toggle::make('is_cliente')->label(__("general.Customer")),
                        Forms\Components\Toggle::make('is_menor')->label(__("general.Minor")),
                    ])
                    ->columns(4)->columnSpanFull(),
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
                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        Forms\Components\Hidden::make('model')->default('FormControl')
                    ])
                    ->columns(4)
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ,
                
                // Forms\Components\Repeater::make('photos')
                //     ->label(__("general.Photos"))
                //     ->relationship()
                //     ->schema([
                //         Forms\Components\FileUpload::make('image'),
                //     ])
                //     ->columns(1)
                //     ->defaultItems(0)
                //     ->columnSpanFull(),
                
                // ->columnSpanFull()

                // Forms\Components\Toggle::make('is_moroso')->label('Moroso'),
                Forms\Components\Textarea::make('observations')
                    ->columnSpanFull()
                    ->label(__('general.Observations')),

                Forms\Components\Select::make('status')
                    ->label(__("general.Status"))
                    ->options(['Pending' => 'Pendiente','Authorized' => 'Autorizado', 'Denied' => 'Denegado'])
                    ->default('Pending')
                    // ->visible()
                    ,

                Forms\Components\Select::make('authorized_user_id')
                    // ->default(Auth::user()->id)
                    ->label(__("general.AuthorizedPer"))
                    ->disabled()
                    ->options(User::all()->pluck('name', 'id')),
                
                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label(__("general.Status"))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pending' => 'Pendiente',
                        'Authorized' => 'Autorizado',
                        'Denied' => 'Denegado',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Authorized' => 'success',
                        'Denied' => 'danger'
                    }),
                    
                Tables\Columns\TextColumn::make('access_type')
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
                Tables\Columns\TextColumn::make('lote_ids')->label(__('general.Lote')),
                Tables\Columns\TextColumn::make('peoples_count')->counts('peoples')->label(__('general.Peoples')),
                Tables\Columns\TextColumn::make('peopleResponsible.phone')            
                    ->copyable()
                    ->label(__('general.peopleResponsiblePhone'))
                    ->copyMessage('Phone copied')
                    ->copyMessageDuration(1500),
                // Tables\Columns\TextColumn::make('autos_count')->counts('autos')->label('Autos'),
                
                Tables\Columns\TextColumn::make('start_date_range')
                    ->date()
                    ->sortable()->label(__('general.start_date_range')),
                // Tables\Columns\TextColumn::make('start_time_range'),
                Tables\Columns\TextColumn::make('end_date_range')
                    ->date()
                    ->sortable()->label(__('general.end_date_range')),
                // Tables\Columns\TextColumn::make('end_time_range'),
                
                
                Tables\Columns\TextColumn::make('authorized_user_id')
                    ->numeric()
                    ->sortable()
                    ->label(__('general.authorized_user_id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\IconColumn::make('is_moroso')
                //     ->boolean()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            'index' => Pages\ListFormControls::route('/'),
            // 'create' => Pages\CreateFormControl::route('/create'),
            'edit' => Pages\EditFormControl::route('/{record}/edit'),
            'view' => Pages\ViewFormControl::route('/{record}'),
        ];
    }
}
