<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Filament\Resources\EmployeeResource\Traits\HasNotesAction;
use App\Filament\Resources\EmployeeResource\Traits\HasGestionAction;
use App\Models\ConstructionCompanie;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use App\Models\Trabajos;
use Filament\Forms\Components\Placeholder;
use App\Models\Works;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Wizard;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Actions\Action as NotificationAction;



class EmployeeResource extends Resource
{
    use HasNotesAction, HasGestionAction;
    
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Gestion de Trabajadores';
    protected static ?string $label = 'trabajador';
    // protected static ?string $navigationGroup = 'Web';

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

            Forms\Components\Select::make('model_origen')
                ->label('Origen')
                ->options([
                    'ConstructionCompanie' => 'Compañías De Construcciones',
                    'Employee' => 'KM314',
                    'Owner' => 'Propietario',
                ])
                ->default(fn(Get $get) => $get('model_origen') ?? (Auth::user()->hasRole('owner') && Auth::user()->owner_id ? 'Owner' : null))
                ->dehydrated(true)
                ->visible(function(){
                    if (Auth::user()->hasRole('owner') && Auth::user()->owner_id) {
                        return false;
                    }
                    return true;
                })
                ->live(),

            Forms\Components\Select::make('model_origen_id')
                ->label('Compañía de origen')
                ->options(function(){
                    return ConstructionCompanie::get()->pluck('name','id')->toArray();
                })->disabled(function(Get $get){
                    return $get('model_origen') == 'ConstructionCompanie' ? false:true;
                })
                ->visible(function(Get $get){
                    return $get('model_origen') == 'ConstructionCompanie' ? true:false;
                })
                ->live(),

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
                            ->required()
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
                    ->default([
                        [
                            'name' => 'DNI (Frente)',
                            'is_required_fecha_vencimiento' => false,
                        ],
                        [
                            'name' => 'DNI (Trasero)',
                            'is_required_fecha_vencimiento' => false,
                        ],
                        [
                            'name' => 'Seguro de Accidentes Personales',
                            'is_required_fecha_vencimiento' => true,
                        ],
                        [
                            'name' => 'Antecedentes Penales',
                            'is_required_fecha_vencimiento' => true,
                        ],
                        [
                            'name' => 'Monotributo',
                            'is_required_fecha_vencimiento' => true,
                        ],

                    ])
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
                ->required(),
            Forms\Components\FileUpload::make('file')
                ->label('Archivo')
                ->required()
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
                        Forms\Components\TimePicker::make('start_time')
                            ->label(__("Hora de entrada"))
                            ->required()
                            ->default('12:00')
                            ->hidden()
                            ->dehydrated(),
                        Forms\Components\TimePicker::make('end_time')
                            ->label(__("Hora de salida"))
                            ->default('23:59')
                            ->hidden()
                            ->dehydrated()
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
                        Wizard\Step::make('Días de trabajo')
                            ->icon('heroicon-m-calendar')
                            ->schema(self::formHorarios()),
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
                                
                Tables\Actions\Action::make('renovar_documentos')
                    ->label('Renovar documentos')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->fillForm(function (Employee $record): array {
                        return [
                            'files' => $record->files()
                                ->where('fecha_vencimiento', '<', now())
                                ->get()
                                ->map(function ($file) {
                                    return [
                                        'id' => $file->id,
                                        'fecha_vencimiento' => $file->fecha_vencimiento,
                                        'file' => [$file->file],
                                        'name' => $file->name,
                                    ];
                                })->toArray()
                        ];
                    })
                    ->form([
                        Placeholder::make('')
                            ->content('Remplaza los documentos vencidos con nuevos archivos y fechas de vencimiento actualizadas. Todos los documentos deben actualizarse para proceder con la renovación.')
                            ->columnSpanFull(),   
                        Repeater::make('files')
                            ->label('Documentos vencidos a renovar')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->grid(2)
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\Hidden::make('name'),
                                DatePicker::make('fecha_vencimiento')
                                    ->label('Fecha de vencimiento del documento')
                                    ->required(),
                                Forms\Components\FileUpload::make('file')
                                    ->label('Archivo')
                                    ->helperText('Presiona la X para eliminar el archivo actual y subir uno nuevo.')
                                    ->required()
                                    ->storeFileNamesIn('attachment_file_names')
                                    ->openable()
                                    ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                        return $file ? $file->getClientOriginalName() : $record->file;
                                    }),
                            ])
                    ])
                    ->action(function (array $data, Employee $record): void {
                        // Validar que haya datos
                        if (empty($data['files'])) {
                            Notification::make()
                                ->title('No se recibieron datos del formulario')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $actualizados = 0;
                        $noActualizados = 0;
                        $documentosNoActualizados = [];
                        
                        // Procesar cada archivo
                        foreach ($data['files'] as $fileData) {
                            $fileRecord = $record->files()->where('id', $fileData['id'])->first();
                            
                            if (!$fileRecord) {
                                continue;
                            }
                            
                            // Verificar si la fecha está vencida
                            $fechaVencimiento = Carbon::parse($fileData['fecha_vencimiento']);
                            if ($fechaVencimiento->isBefore(now()->startOfDay())) {
                                $noActualizados++;
                                $documentosNoActualizados[] = $fileData['name'] ?? "Documento ID {$fileData['id']}";
                                continue;
                            }
                            
                            // Actualizar fecha de vencimiento
                            $fileRecord->fecha_vencimiento = $fileData['fecha_vencimiento'];
                            
                            // Actualizar archivo solo si se subió uno nuevo
                            // Cuando Filament procesa el FileUpload, el archivo ya está guardado
                            // y $fileData['file'] contiene la ruta del nuevo archivo
                            if (isset($fileData['file']) && $fileData['file'] !== $fileRecord->file) {
                                // Si hay un archivo nuevo diferente al actual
                                $fileRecord->file = $fileData['file'];
                            }
                            
                            $fileRecord->save();
                            $actualizados++;
                        }
                        
                        // Mostrar notificación según el resultado
                        if ($actualizados > 0 && $noActualizados === 0) {
                            Notification::make()
                                ->title('Documentos renovados exitosamente')
                                ->body("Se actualizaron {$actualizados} documento(s).")
                                ->success()
                                ->send();

                                $record->status = 'pendiente';
                                $record->save();

                        } elseif ($actualizados > 0 && $noActualizados > 0) {
                            Notification::make()
                                ->title('Renovación parcial')
                                ->body("Se actualizaron {$actualizados} documento(s). Los siguientes documentos no se actualizaron por tener fechas vencidas: " . implode(', ', $documentosNoActualizados))
                                ->warning()
                                ->send();
                                $record->status = 'pendiente';
                                $record->save();
                        } else {
                            Notification::make()
                                ->title('No se actualizó ningún documento')
                                ->body('Todos los documentos tienen fechas de vencimiento inválidas (vencidas).')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function ($record) {
                        return  $record->vencidosFile();
                        // $vencimientos = self::isVencimientos($record);
                        // return $vencimientos['isVencido'];
                    }),
                
                ActionGroup::make([
                    self::getGestionarAutosTableAction(),
                    self::getGestionarHorariosTableAction(),
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
