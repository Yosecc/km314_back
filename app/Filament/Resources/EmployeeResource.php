<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\ConstructionCompanie;
use App\Models\Employee;
use App\Models\Owner;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Trabajadores';
    protected static ?string $label = 'trabajador';
    // protected static ?string $navigationGroup = 'Web';

    public static function getPluralModelLabel(): string
    {
        return 'trabajadores';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                ->schema([
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
                        ->default(32)
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

                    DatePicker::make('fecha_vencimiento_seguro')
                        ->label('Fecha de vencimiento del seguro personal')
                        ->displayFormat('d/m/Y')
                        ->live()
                        ,
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
                ])->columns(2),

                Forms\Components\Repeater::make('autos')
                    ->relationship()
                    ->mutateRelationshipDataBeforeFillUsing(function ($record, $data) {
                        // dd($record->autos, $data);
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
                            ->default('Employee')
                            // ->maxLength(255),
                    ])
                    ->defaultItems(0)
                    ->columns(2),
                    

               
                Forms\Components\Repeater::make('horarios')
                    ->relationship()
                    ->schema([
                        Placeholder::make('')->content('Selecciona los dias y el horario de trabajo del empleado.'),
                        Forms\Components\Select::make('day_of_week')
                            ->label(__("Día"))
                            ->options([
                                'Domingo' => 'Domingo', 'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miercoles', 'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sabado'
                            ])
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label(__("Hora de entrada"))
                            ->required(),
                        Forms\Components\TimePicker::make('end_time')
                            ->label(__("Hora de salida"))
                            ->required(),
                    ])
                    ->minItems(1)
                    ->defaultItems(1)
                    ->columns(3),

                Repeater::make('files')
                    ->relationship()
                    ->label('Documentos')
                    ->schema([

                        Forms\Components\TextInput::make('name')->label('Descripción'),
                        // DatePicker::make('fecha_vencimiento')->label('Fecha de vencimiento'),
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo')
                            ->required()
                            ->storeFileNamesIn('attachment_file_names')
                            ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                return $file ? $file->getClientOriginalName() : $record->file;
                            })
                            ->disabled(function($context, Get $get){
                                return $context == 'edit' ? true:false;
                            }),

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
                    ->defaultItems(1)
                    ->default([
                        [
                            'name' => 'Seguro Personal',
                        ]
                    ])
                    ->columns(1)
            ])->columns(1);
    }

    public static function isVencimientos($record)
    {
        $color = '';
        $texto = '';
        $status = false;
        if($record->isVencidoSeguro()){
            $color = "danger";
            $texto = "Seguro vencido";
            $status = true;
        }

        $vencidosFile = $record->vencidosFile();
        if($vencidosFile){
            $color = "danger";
            $texto = "Documentos  vencidos: ". implode($vencidosFile);
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
                Tables\Columns\TextColumn::make('work.name')
                    ->label(__("general.Work"))
                    ->numeric()
                    ->sortable()
                    ->color(fn (Employee $record) => self::isVencimientos($record)['color'])
                    ->tooltip(fn (Employee $record) => self::isVencimientos($record)['texto'])
                    ,
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
                Tables\Actions\Action::make('renovar_documentos')
                    ->label('Renovar Seguro')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Section::make('Renovar Seguro')
                            ->schema([
                                Forms\Components\DatePicker::make('fecha_vencimiento_seguro')
                                    ->label('Fecha de vencimiento del seguro')
                                    ->displayFormat('d/m/Y')
                                    ->required(),
                            ])
                            ->visible(fn ($record) => $record->isVencidoSeguro()),

                       Forms\Components\Section::make('Renovar Documentos')
                            ->schema([
                                Forms\Components\TextInput::make('file_name')
                                    ->label('Descripción del documento')
                                    ->required()
                                    ->default('Seguro'),
                                Forms\Components\DatePicker::make('file_fecha_vencimiento')
                                    ->label('Fecha de vencimiento')
                                    ->displayFormat('d/m/Y')
                                    ->required(),
                                Forms\Components\FileUpload::make('file_upload')
                                    ->label('Archivo')
                                    ->required()
                                    ->storeFileNamesIn('attachment_file_names')
                                    ->getUploadedFileNameForStorageUsing(function ($file) {
                                        return $file->getClientOriginalName();
                                    }),
                            ])
                            ->visible(fn ($record) => $record->isVencidoSeguro()),
                    ])
                    ->action(function ($record, $data) {
                        // Actualizar fecha de vencimiento del seguro si está presente
                        if (isset($data['fecha_vencimiento_seguro'])) {
                            $record->update(['fecha_vencimiento_seguro' => $data['fecha_vencimiento_seguro']]);
                        }

                         // Crear nuevo archivo si está presente
                        if (isset($data['file_upload'])) {
                            $record->files()->create([
                                'name' => $data['file_name'],
                                'fecha_vencimiento' => $data['file_fecha_vencimiento'],
                                'file' => $data['file_upload'],
                            ]);
                        }

                        Notification::make()
                            ->title('Documentos renovados exitosamente')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $vencimientos = self::isVencimientos($record);
                        return $vencimientos['isVencido'];
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        // Si es owner y el empleado está aprobado, ocultar el botón de editar
                        if (Auth::user()->hasRole('owner') && $record->status === 'aprobado') {
                            return false;
                        }
                        return true;
                    }),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageEmployees::route('/'),
            'view' => Pages\ViewEmployeeResource::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
