<?php

namespace App\Filament\Resources\ConstructionCompanieResource\RelationManagers;

use App\Models\ConstructionCompanie;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmpleadosRelationManager extends RelationManager
{
    protected static string $relationship = 'empleados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('work_id')
                    ->label(__("general.Work"))
                    ->required()
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

                DatePicker::make('fecha_vencimiento_seguro')
                    ->label('Fecha de vencimiento del seguro')
                    ->displayFormat('d/m/Y')
                    ->live(),

                Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),

                Forms\Components\Hidden::make('model_origen')->default('ConstructionCompanie'),

                Forms\Components\Hidden::make('model_origen_id')
                    ->label(__(''))
                    ->default(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->id;
                    }),

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
                    ->columns(2)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('horarios')
                    ->relationship()
                    ->schema([
                        // employee_id
                        Forms\Components\Select::make('day_of_week')
                            ->label(__("Día"))
                            //->unique(ignoreRecord: true)
                            ->options([
                                'Domingo' => 'Domingo', 'Lunes' => 'Lunes', 'Martes' => 'Martes', 'Miercoles' => 'Miercoles', 'Jueves' => 'Jueves', 'Viernes' => 'Viernes', 'Sabado' => 'Sabado'
                            ]),
                        Forms\Components\TimePicker::make('start_time')->label(__("Hora de entrada")),
                        Forms\Components\TimePicker::make('end_time')->label(__("Hora de salida")),
                    ])
                    ->defaultItems(0)
                    ->columns(3)
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('files')
                    ->relationship()
                    ->label('Documentos')
                    ->schema([

                        Forms\Components\TextInput::make('name')->label('Descripción'),
                        DatePicker::make('fecha_vencimiento')->label('Fecha de vencimiento'),
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo')
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
                    ->defaultItems(0)
                    ->columns(1)
                    ->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
