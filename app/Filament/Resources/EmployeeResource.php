<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Works;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Employee;
use App\Models\Trabajos;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FilesRequired;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use App\Models\ConstructionCompanie;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\EmployeeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use App\Filament\Resources\EmployeeResource\Traits\HasGestionAction;



class EmployeeResource extends Resource
{
    use HasNotesAction, HasGestionAction;
    
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Gestión de Trabajadores';
    protected static ?string $label = 'trabajador';
    // protected static ?string $navigationGroup = 'Web';

    public static function canViewAny(): bool
    {
        // Si el usuario no ha aceptado los términos, no puede ver el recurso
        if(Auth::user()->hasRole('owner')){
            $user = auth()->user();
            return $user && $user->is_terms_condition;
        }

        if(Auth::user()->hasRole(['super_admin','admin'])){
            return true;
        }

        return auth()->user()->can('view_any_employee');
    }

    public static function getPluralModelLabel(): string
    {
        return 'trabajadores';
    }

    private static function formDatosPersonales()
    {
        return [
            Forms\Components\Hidden::make('status')
                ->default(function(){
                    if (Auth::user()->hasRole('owner')) {
                        return 'pendiente';
                    }
                    return 'aprobado'; // Para admin u otros roles
                }),
            Forms\Components\Select::make('work_id')
                ->label(__("general.Work"))
                ->required()
                ->default(37)
                ->visible(function(){
                    if (Auth::user()->hasRole('super_admin')) {
                        return true;
                    }
                    return false;
                })
                ->relationship(name: 'work', titleAttribute: 'name'),

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
                ->required()
                ->numeric(),

            Forms\Components\Hidden::make('user_id')->disabled(fn($context)=> $context == 'edit')->default(Auth::user()->id),

            Repeater::make('employeeOrigens')
                ->label('Origen adicional del trabajador')
                // ->helperText('Solo para Compañías de Construcción o KM314. Los propietarios se gestionan más abajo.')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('model')
                        ->label('Tipo de origen')
                        ->options([
                            'ConstructionCompanie' => 'Compañía de Construcción',
                            'Employee' => 'KM314',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function(Set $set){
                            $set('model_id', null);
                        }),
                    
                    Forms\Components\Select::make('model_id')
                        ->label(function(Get $get){
                            $model = $get('model');
                            return match($model) {
                                'ConstructionCompanie' => 'Seleccione la compañía',
                                'Employee' => 'KM314',
                                default => 'Seleccione el origen'
                            };
                        })
                        ->options(function(Get $get){
                            $model = $get('model');
                            return match($model) {
                                'ConstructionCompanie' => ConstructionCompanie::get()->pluck('name', 'id')->toArray(),
                                default => []
                            };
                        })
                        ->searchable()
                        ->required(function(Get $get){
                            $model = $get('model');
                            return $model === 'ConstructionCompanie';
                        })
                        ->visible(function(Get $get){
                            $model = $get('model');
                            return $model === 'ConstructionCompanie';
                        })
                        ->live(),
                ])
                ->addActionLabel('Agregar origen')
                ->columns(1)
                ->collapsible()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => 
                    isset($state['model']) 
                        ? ($state['model'] === 'ConstructionCompanie' && isset($state['model_id'])
                            ? 'Compañía: ' . (ConstructionCompanie::find($state['model_id'])->name ?? 'N/A')
                            : ($state['model'] === 'Employee' ? 'KM314' : 'Origen sin configurar'))
                        : 'Origen sin configurar'
                )
                ->visible(function(){
                    if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                        return false;
                    }
                    return true;
                })
                ,

            // DatePicker::make('fecha_vencimiento_seguro')
            //     ->label('Fecha de vencimiento del seguro personal')
            //     ->displayFormat('d/m/Y')
            //     ->required()
            //     ->default(Carbon::now()->addMonths(3))
            //     ->hidden(true)
            //     ->dehydrated()
            //     ->live()
            //     ,
            // Forms\Components\Select::make('owner_id')
            //     ->label('Propietario')
            //     ->searchable()
            //     ->options(function(){
            //         return Owner::get()->map(function($owner){
            //             $owner['texto'] = $owner->nombres();
            //             return $owner;
            //         })->pluck('texto','id')->toArray();
            //     })
            //     ->default(function(){
            //         if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            //             return Auth::user()->owner_id;
            //         }
            //     })
            //     ->visible(function(){
            //         if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
            //             return false;
            //         }
            //         return true;
            //     }),
        // Cambiar el select de owner_id por un select múltiple
        Forms\Components\Select::make('owners')
            ->label('Propietarios')
            ->multiple()
            ->searchable()
            ->relationship('owners', 'first_name')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombres())
            ->preload()
            ->default(function($record){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return [Auth::user()->owner_id];
                }
                // Si es edición y tiene owner_id, incluirlo por defecto
                return $record && $record->owner_id ? [$record->owner_id] : [];
            })
            ->visible(function(){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return false;
                }
                return true;
            }),

        // Mantener temporalmente el campo owner_id oculto para compatibilidad
        Forms\Components\Hidden::make('owner_id')
            ->default(function(){
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    return Auth::user()->owner_id;
                }
            }),

            Forms\Components\TextInput::make('observations')
                ->label('Observaciones del trabajo a realizar')
                ->columnSpanFull(),
        ];
    }

    private static function getArchivos($type)
    {
        $filesRequired = FilesRequired::where('type', $type)->first();
        if (!$filesRequired) {
            return [];
        }

        $archivos = collect($filesRequired->required)->map(function($item){
            return [
                'name' => $item['document'],
                'is_required_fecha_vencimiento' => $item['date_is_required'],
                'is_required' => $item['is_required'],
            ];
        });

        return $archivos->toArray();
    }

    private static function formArchivosPersonales()
    {

        

        return [
            Repeater::make('files')
                    ->relationship()
                    ->label('Documentos')
                    ->schema([
                        // TextEntry::make('name'),
                        Forms\Components\Hidden::make('name')->dehydrated(),
                        DatePicker::make('fecha_vencimiento')
                            ->label('Fecha de vencimiento del documento')
                            ->extraFieldWrapperAttributes(function(Get $get, $state){
                                        if($state  && Carbon::parse($state)->isPast()){
                                            return ['style' => 'border-color: crimson;border-width: 1px;border-radius: 8px;padding: 10px;'];
                                        }
                                        return [];
                                    })
                            ->hidden(function(Get $get, Set $set, $context){
                                if($context == 'edit' || $context == 'view'){
                                    return false;
                                }
                                $is_required = $context == 'create' && $get('is_required_fecha_vencimiento') ?? false;
                                return !$is_required;
                            })
                            ->required(function(Get $get, Set $set, $context){
                                $is_required = $get('is_required_fecha_vencimiento') ?? false;
                                return $is_required;
                            }),
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo')
                            ->required(function(Get $get, Set $set, $context){
                                $is_required = $get('is_required') ?? false;
                                return $is_required;
                            })
                            ->storeFileNamesIn('attachment_file_names')
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                return $file ? $file->getClientOriginalName() : $record->file;
                            })
                            ->disabled(function($context, Get $get){
                                return $context == 'edit' ? true:false;
                            }),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(5)
                    ->addable(false)
                    ->deletable(false)
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->default(self::getArchivos('employee'))
                    ->grid(2)
                    ->columns(1)
        ];
    }

    private static function camposAutosFiles()
    {
        return [
            Forms\Components\Hidden::make('name')->dehydrated(),
            DatePicker::make('fecha_vencimiento')
                ->label('Fecha de vencimiento del documento')
                ->extraFieldWrapperAttributes(function(Get $get, $state){
                    if(Carbon::parse($state)->isPast()){
                        return ['style' => 'border-color: crimson;border-width: 1px;border-radius: 8px;padding: 10px;'];
                    }
                    return [];
                })
                ->required(function(Get $get, Set $set, $context){
                                $is_required = $get('is_required_fecha_vencimiento') ?? false;
                                return $is_required;
                            }),
            Forms\Components\FileUpload::make('file')
                ->label('Archivo')
                ->required(function(Get $get, Set $set, $context){
                                $is_required = $get('is_required') ?? false;
                                return $is_required;
                            })
                ->storeFileNamesIn('attachment_file_names')
                ->openable()
                ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                    return $file ? $file->getClientOriginalName() : $record->file;
                })
        ];
    }

    private static function camposAutos()
    {
        return [
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
                // ->maxLength(255),
            Forms\Components\Hidden::make('model')
                ->default('Employee'),
                // ->maxLength(255),
            Repeater::make('files')
                ->relationship()
                ->label('Documentos del vehículo')
                ->schema(self::camposAutosFiles())
                ->defaultItems(3)
                ->minItems(3)
                ->maxItems(3)
                ->addable(false)
                ->deletable(false)
                ->grid(2)
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->default(self::getArchivos('car'))
                ->columns(1)
                ->columnSpanFull(),
        ];
    }
    private static function formAutos()
    {
        return [
            Forms\Components\Repeater::make('autos')
                    ->label('Vehículos')
                    ->relationship()
                    ->mutateRelationshipDataBeforeFillUsing(function ($record, $data) {
                        $data['model'] = $record->autos->where('id', $data['id'])->first()->model;
                        return $data;
                    })
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
                            // ->maxLength(255),
                        Forms\Components\Hidden::make('model')
                            ->default('Employee'),
                            // ->maxLength(255),
                        Repeater::make('files')
                            ->relationship()
                            ->label('Documentos del vehículo')
                            ->schema(self::camposAutosFiles())
                            ->defaultItems(3)
                            ->minItems(3)
                            ->maxItems(3)
                            ->addable(false)
                            ->deletable(false)
                            ->grid(2)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->default(self::getArchivos('car'))
                            ->columns(1)
                            ->columnSpanFull(),
                    ])
                    ->itemLabel('Información del vehículo')
                    ->addActionLabel('Agregar vehículo')
                    ->defaultItems(0)
                    ->columns(2)
        ];
    }

    private static function formHorarios()
    {
        return [
            Forms\Components\Repeater::make('horarios')
                    ->relationship()
                    ->schema([
                        // Placeholder::make('')->content('Selecciona el dia y el horario de trabajo')->columnSpanFull(),
                        Forms\Components\Select::make('day_of_week')
                            ->label(__("Día"))
                            ->options([
                                'Domingo' => 'Domingo', 'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miercoles', 'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sabado'
                            ])
                            ->required(),
                        Forms\Components\Hidden::make('start_time')
                            ->label(__("Hora de entrada"))
                            ->required()
                            ->default('00:00'),
                        Forms\Components\Hidden::make('end_time')
                            ->label(__("Hora de salida"))
                            ->default('23:59')
                            ->required(),
                    ])
                    ->itemLabel('Selecciona el día de trabajo')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->grid(2)
                    ->columns(1),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                        Wizard\Step::make('Información')
                            ->icon('heroicon-m-information-circle')
                            ->schema(self::formDatosPersonales())
                            ->columns(2),
                        Wizard\Step::make('Documentos personales')
                            ->icon('heroicon-m-document-text')
                            ->schema(self::formArchivosPersonales()),
                        Wizard\Step::make('Vehiculos')
                            ->icon('heroicon-m-truck')
                            ->schema(self::formAutos()),
                        // Wizard\Step::make('Días de trabajo')
                        //     ->icon('heroicon-m-calendar')
                        //     ->schema(self::formHorarios()),
                    ])
                    ->skippable(function ($context) {
                        return $context == 'edit' || $context == 'view';
                    }),                  
            ])->columns(1);
    }

    public static function isVencimientos($record)
    {
        $color = '';
        $texto = '';
        $status = false;
        
        if($record->isVencidoSeguro()){
            $color = "warning";
            $texto = "Trabajador pendiente de reverificación de datos.";
            $status = true;
        }

        $vencidosFile = $record->vencidosFile();
        if($vencidosFile){
            $color = "danger";
            $texto = "Documentos  vencidos: ". implode($vencidosFile);
            $status = true;
        }

        $vencidosAutosFile = $record->vencidosAutosFile();
        if($vencidosAutosFile){
            $color = "danger";
            $texto = "Documentos de autos vencidos: ". implode($vencidosAutosFile);
            $status = true;
        }

        return [
            'color' => $color,
            'texto' => $texto,
            'isVencido' => $status,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                    $query->where(function($q) {
                        $q->whereHas('owners', function($ownerQuery) {
                            $ownerQuery->where('owner_id', Auth::user()->owner_id);
                        })->orWhere('owner_id', Auth::user()->owner_id);
                    });
                }
                return $query->orderBy('created_at', 'desc');
            })
            ->columns([
                // Tables\Columns\TextColumn::make('work.name')
                //     ->label(__("general.Work"))
                //     ->numeric()
                //     ->sortable()
                //     ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                //     ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                //     ,
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__("general.Phone"))
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label(__('general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('status')
                    ->label('Estado')
                    ->searchable()
                    ->tooltip(fn (string $state): string => match ($state) {
                        'rechazado' => 'El trabajador ha sido rechazado.',
                        'pendiente' => 'El trabajador está pendiente de aprobación.',
                        'aprobado' => 'El trabajador ha sido aprobado.',
                        default => 'Estado desconocido.',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'rechazado' => 'heroicon-o-x-circle',
                        'pendiente' => 'heroicon-o-clock',
                        'aprobado' => 'heroicon-o-check-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'aprobado' => 'success',
                        'rechazado' => 'danger',
                        default => 'gray',
                    })
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        // Si es owner y el empleado está aprobado, ocultar el botón de editar
                        if (Auth::user()->hasRole('owner') && $record->status === 'aprobado') {
                            return false;
                        }
                        return true;
                    }),
                // Botón de notificaciones en la tabla
                self::getNotesTableAction(),

                Tables\Actions\Action::make('show_qr')
                    ->label('Ver QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading('Código de Acceso Rápido')
                    ->modalDescription(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->modalContent(fn ($record) => view('components.qr-modal', [
                        'record' => $record,
                        'qrCode' => $record->generateQrCode(),
                        'entityType' => 'Empleado'
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->visible(function ($record) {
                        return Auth::user()->hasRole('super_admin') || Auth::user()->hasRole('admin');
                    }),

                Tables\Actions\Action::make('verificar_seguro')
                    ->label('Verificar trabajador')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->action(function (Employee $record): void {
                        $record->fecha_vencimiento_seguro = Carbon::now()->addMonths(6);
                        $record->save();

                        Notification::make()
                            ->title('Trabajador verificado')
                            ->body('La fecha de reverificación se ha actualizado correctamente.')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        return Auth::user()->hasRole('super_admin') && $record->isVencidoSeguro();
                    }),
                                
                self::getRenovarDocumentosTableAction(),
                
                ActionGroup::make([
                    self::getGestionarAutosTableAction(),
                    // self::getGestionarHorariosTableAction(),
                ])->color('info'),
                
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
            'index' => Pages\ManageEmployees::route('/'),
            'view' => Pages\ViewEmployeeResource::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
