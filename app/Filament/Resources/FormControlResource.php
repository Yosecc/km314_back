<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Lote;
use App\Models\User;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Trabajos;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FormControl;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FormControlResource\Pages;
use App\Filament\Resources\FormControlResource\RelationManagers;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
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

                        Radio::make('tipo_trabajo')
                            ->options(Trabajos::get()->pluck('name','name')->toArray())
                            ->visible(function(Get $get){
                                return collect($get('income_type'))->contains('Trabajador');
                            })


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
                        Forms\Components\Toggle::make('is_acompanante')->label(__("general.Acompanante")),
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

                Forms\Components\Repeater::make('files')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label(__("general.Description"))
                            ->maxLength(255),
                        Forms\Components\Hidden::make('form_control_id'),
                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        FileUpload::make('file')
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->columnSpanFull()
                    ,


                Forms\Components\TextInput::make('observations')
                    ->columnSpanFull()
                    ->label(__('general.Observations')),

                Forms\Components\Hidden::make('status')
                    ->label(__("general.Status"))
                    // ->options(['Pending' => 'Pendiente','Authorized' => 'Autorizado', 'Denied' => 'Denegado'])
                    ->default('Pending')
                    ->afterStateUpdated(function (Set $set) {
                        $set('authorized_user_id', Auth::user()->id);
                    })
                    // ->readonly()
                    ->live()
                    ->visible(Auth::user()->hasAnyRole([1])),

                Forms\Components\Hidden::make('authorized_user_id')
                    // ->default(Auth::user()->id)
                    ->label(__("general.AuthorizedPer"))
                    ->live()
                    // ->readOnly()
                    // ->options(User::all()->pluck('name', 'id'))
                    ,

                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),

                Forms\Components\Select::make('owner_id')
                    // ->required()
                    ->relationship(name: 'owner')
                    // ->disabled()
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}")
                    ->label(__("general.Owner")),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('status','desc')->orderBy('created_at','desc'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                ->sortable()
                ->searchable(),
                Tables\Columns\TextColumn::make('status')
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
                    }),
                // Tables\Columns\TextColumn::make('lote_ids')->label(__('general.Lote')),
                Tables\Columns\TextColumn::make('peoples_count')->counts('peoples')->label(__('general.Peoples')),
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deniedUser.name')
                    ->numeric()
                    ->sortable()
                    ->label(__('general.denied_user_id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\IconColumn::make('is_moroso')
                //     ->boolean()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                            DatePicker::make('created_from_'),
                            DatePicker::make('created_until_'),
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
                            DatePicker::make('created_from'),
                            DatePicker::make('created_until'),
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
                            DatePicker::make('created_from'),
                                            DatePicker::make('created_until'),
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
                    ->options([
                        'Authorized' => 'Authorized',
                        'Denied' => 'Denied',
                        'Pending' => 'Pending',
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
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->icon('heroicon-m-hand-thumb-up')
                    ->color('success')
                    ->label('Aprobar')
                    ->hidden(function(FormControl $record){
                        return $record->isActive() || $record->isExpirado() || $record->isVencido() ? true : false;
                    }),
                Action::make('rechazar')
                    ->action(function(FormControl $record){
                        $record->rechazar();
                        Notification::make()
                            ->title('Formulario rechzado')
                            ->success()
                            ->send();
                    })
                    ->button()
                    ->requiresConfirmation()
                    ->icon('heroicon-m-hand-thumb-down')
                    ->color('danger')
                    ->label('Rechazar')
                    ->hidden(function(FormControl $record){
                        return $record->isDenied() || $record->isExpirado() || $record->isVencido() ? true : false;
                    }),
                Tables\Actions\EditAction::make(),
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
            // 'create' => Pages\CreateFormControl::route('/create'),
            'edit' => Pages\EditFormControl::route('/{record}/edit'),
            'view' => Pages\ViewFormControl::route('/{record}'),
        ];
    }
}
