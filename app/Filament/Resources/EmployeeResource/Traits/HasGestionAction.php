<?php

namespace App\Filament\Resources\EmployeeResource\Traits;

use App\Models\Employee;
use App\Models\EmployeeNote;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\Action as PageAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Forms\Components\Placeholder;

trait HasGestionAction
{
    public static function getGestionarAutos()
    {
        return Tables\Actions\Action::make('gestionarAutos')
            ->label('Gestionar vehículos')
            ->icon('heroicon-o-truck')
            ->color('primary')
            ->visible(function ($record) {
                return $record->status === 'aprobado';
            })
            ->fillForm(function (Employee $record): array {
                return [
                    'autos' => $record->autos->map(function ($auto) {
                        return [
                            'id' => $auto->id,
                            'marca' => $auto->marca,
                            'modelo' => $auto->modelo,
                            'patente' => $auto->patente,
                            'color' => $auto->color,
                            'user_id' => $auto->user_id,
                            'model' => $auto->model,
                            'files' => $auto->files->map(function ($file) {
                                return [
                                    'id' => $file->id,
                                    'name' => $file->name,
                                    'fecha_vencimiento' => $file->fecha_vencimiento,
                                    'file' => $file->file,
                                ];
                            })->toArray()
                        ];
                    })->toArray()
                ];
            })
            ->form([
                Placeholder::make('')
                    ->content('Aquí puedes gestionar los vehículos del trabajador. Puedes agregar nuevos vehículos o eliminar los existentes. Los cambios pasarán por un proceso de verificación.')
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('autos')
                    ->label('Vehículos')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\TextInput::make('marca')
                            ->label(__("general.Marca"))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('modelo')
                            ->label(__("general.Modelo"))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('patente')
                            ->label(__("general.Patente"))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('color')
                            ->label(__("general.Color"))
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                        Forms\Components\Hidden::make('model')->default('Employee'),
                        Repeater::make('files')
                            ->label('Documentos del vehículo')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
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
                                        return $file ? $file->getClientOriginalName() : ($record ? $record->file : null);
                                    })
                            ])
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
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['patente']) ? "Vehículo: {$state['patente']}" : 'Nuevo vehículo'
                    )
                    ->addActionLabel('Agregar vehículo')
                    ->defaultItems(0)
                    ->deletable(true)
                    ->reorderable(false)
                    ->columns(2)
                    ->columnSpanFull()
            ])
            ->action(function (Employee $record, array $data): void {
                $autosActuales = $record->autos->pluck('id')->toArray();
                $autosFormulario = collect($data['autos'])->pluck('id')->filter()->toArray();
                
                // Autos a eliminar (están en BD pero no en el formulario)
                $autosEliminar = array_diff($autosActuales, $autosFormulario);
                
                // Eliminar autos y sus archivos
                foreach ($autosEliminar as $autoId) {
                    $auto = $record->autos()->find($autoId);
                    if ($auto) {
                        // Eliminar archivos físicos
                        foreach ($auto->files as $file) {
                            if (Storage::exists($file->file)) {
                                Storage::delete($file->file);
                            }
                            $file->delete();
                        }
                        $auto->delete();
                    }
                }
                
                // Procesar autos del formulario
                foreach ($data['autos'] as $autoData) {
                    if (isset($autoData['id']) && $autoData['id']) {
                        // Actualizar auto existente
                        $auto = $record->autos()->find($autoData['id']);
                        if ($auto) {
                            $auto->update([
                                'marca' => $autoData['marca'],
                                'modelo' => $autoData['modelo'],
                                'patente' => $autoData['patente'],
                                'color' => $autoData['color'],
                            ]);
                            
                            // Actualizar archivos
                            foreach ($autoData['files'] as $fileData) {
                                if (isset($fileData['id']) && $fileData['id']) {
                                    $file = $auto->files()->find($fileData['id']);
                                    if ($file) {
                                        $file->update([
                                            'fecha_vencimiento' => $fileData['fecha_vencimiento'],
                                            'file' => is_array($fileData['file']) ? $fileData['file'][0] : $fileData['file'],
                                        ]);
                                    }
                                }
                            }
                        }
                    } else {
                        // Crear nuevo auto
                        $nuevoAuto = $record->autos()->create([
                            'marca' => $autoData['marca'],
                            'modelo' => $autoData['modelo'],
                            'patente' => $autoData['patente'],
                            'color' => $autoData['color'],
                            'user_id' => Auth::user()->id,
                            'model' => 'Employee',
                            'model_id' => $record->id,
                        ]);
                        
                        // Crear archivos para el nuevo auto
                        foreach ($autoData['files'] as $fileData) {
                            if (isset($fileData['file'])) {
                                $nuevoAuto->files()->create([
                                    'name' => $fileData['name'],
                                    'fecha_vencimiento' => $fileData['fecha_vencimiento'],
                                    'file' => is_array($fileData['file']) ? $fileData['file'][0] : $fileData['file'],
                                ]);
                            }
                        }
                    }
                }
                
                Notification::make()
                    ->title('Vehículos actualizados')
                    ->body('Los cambios en los vehículos pasarán por un proceso de verificación.')
                    ->success()
                    ->send();

                $recipient = User::whereHas("roles", function($q){ 
                    $q->whereIn("name", ["super_admin","admin"]); 
                })->get();

                Notification::make()
                    ->title('Un propietario ha modificado los vehículos de un trabajador aprobado. Ir a Gestión de Trabajadores.')
                    ->actions([
                            NotificationAction::make('Ver trabajador')
                                ->button()
                                ->url(route('filament.admin.resources.employees.view', $record), shouldOpenInNewTab: true)
                        ])
                    ->sendToDatabase($recipient);

                $record->status = 'pendiente';
                $record->save();
            });
    }

    public static function getGestionarHorarios()
    {
        return Tables\Actions\Action::make('gestionarHorarios')
            ->label('Gestionar horarios')
            ->icon('heroicon-o-clock')
            ->color('info')
            ->visible(function ($record) {
                return $record->status === 'aprobado';
            })
            ->fillForm(function (Employee $record): array {
                return [
                    'horarios' => $record->horarios->map(function ($horario) {
                        return [
                            'id' => $horario->id,
                            'day_of_week' => $horario->day_of_week,
                            'start_time' => $horario->start_time,
                            'end_time' => $horario->end_time,
                        ];
                    })->toArray()
                ];
            })
            ->form([
                Placeholder::make('')
                    ->content('Aquí puedes gestionar los días de trabajo del empleado. Puedes agregar nuevos días o eliminar los existentes. Los cambios pasarán por un proceso de verificación.')
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('horarios')
                    ->label('Horarios de trabajo')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Select::make('day_of_week')
                            ->label('Día de la semana')
                            ->options([
                                'Domingo' => 'Domingo',
                                'Lunes' => 'Lunes',
                                'Martes' => 'Martes',
                                'Miercoles' => 'Miércoles',
                                'Jueves' => 'Jueves',
                                'Viernes' => 'Viernes',
                                'Sabado' => 'Sábado'
                            ])
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Hidden::make('start_time')
                            ->default('12:00')
                            ->dehydrated(),
                        Forms\Components\Hidden::make('end_time')
                            ->default('23:59')
                            ->dehydrated(),
                    ])
                    ->itemLabel(fn (array $state): ?string => 
                        isset($state['day_of_week']) ? $state['day_of_week'] : 'Nuevo horario'
                    )
                    ->addActionLabel('Agregar día de trabajo')
                    ->defaultItems(0)
                    ->deletable(true)
                    ->reorderable(false)
                    ->grid(2)
                    ->columns(1)
                    ->columnSpanFull()
            ])
            ->action(function (Employee $record, array $data): void {
                $horariosActuales = $record->horarios->pluck('id')->toArray();
                $horariosFormulario = collect($data['horarios'])->pluck('id')->filter()->toArray();
                
                // Horarios a eliminar (están en BD pero no en el formulario)
                $horariosEliminar = array_diff($horariosActuales, $horariosFormulario);
                
                // Eliminar horarios
                foreach ($horariosEliminar as $horarioId) {
                    $horario = $record->horarios()->find($horarioId);
                    if ($horario) {
                        $horario->delete();
                    }
                }
                
                // Procesar horarios del formulario
                foreach ($data['horarios'] as $horarioData) {
                    if (isset($horarioData['id']) && $horarioData['id']) {
                        // Actualizar horario existente
                        $horario = $record->horarios()->find($horarioData['id']);
                        if ($horario) {
                            $horario->update([
                                'day_of_week' => $horarioData['day_of_week'],
                                'start_time' => $horarioData['start_time'],
                                'end_time' => $horarioData['end_time'],
                            ]);
                        }
                    } else {
                        // Crear nuevo horario
                        $record->horarios()->create([
                            'day_of_week' => $horarioData['day_of_week'],
                            'start_time' => $horarioData['start_time'],
                            'end_time' => $horarioData['end_time'],
                        ]);
                    }
                }
                
                Notification::make()
                    ->title('Horarios actualizados')
                    ->body('Los cambios en los horarios pasarán por un proceso de verificación.')
                    ->success()
                    ->send();

                $recipient = User::whereHas("roles", function($q){ 
                    $q->whereIn("name", ["super_admin","admin"]); 
                })->get();

                Notification::make()
                    ->title('Un propietario ha modificado los horarios de un trabajador aprobado. Ir a Gestión de Trabajadores.')
                    ->danger()
                    ->actions([
                            NotificationAction::make('Ver trabajador')
                                ->button()
                                ->url(route('filament.admin.resources.employees.view', $record), shouldOpenInNewTab: true)
                        ])
                    ->sendToDatabase($recipient);

                $record->status = 'pendiente';
                $record->save();
            });
    }
}