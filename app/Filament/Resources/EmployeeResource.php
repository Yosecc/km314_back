<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Works;
use App\Models\Employee;
use App\Models\ConstructionCompanie;
use App\Models\Trabajos;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\EmployeeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use Filament\Forms\Set;
use Filament\Forms\Get;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $label = 'empleado';
    // protected static ?string $navigationGroup = 'Web';

    // public static function getPluralModelLabel(): string
    // {
    //     return 'configuraciones';
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
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
                    Forms\Components\Hidden::make('user_id')->default(Auth::user()->id),
                    Forms\Components\Select::make('trabajo_id')
                        ->label('Tipo de trabajo/Cargo')
                        ->options(Trabajos::get()->pluck('name','id')->toArray()),
                    Forms\Components\Select::make('model_origen')
                        ->label('Compañía de origen')
                        ->options([
                            'ConstructionCompanie' => 'Compañías De Construcciones',
                            'Employee' => 'KM314'
                        ])
                        // ->afterStateUpdated(function($state,Set $set){

                        // })
                        ->live(),
                    Forms\Components\Select::make('model_origen_id')
                        ->options(function(){
                            return ConstructionCompanie::get()->pluck('name','id')->toArray();
                        })->disabled(function(Get $get){
                            return $get('model_origen') == 'ConstructionCompanie' ? false:true;
                        })
                        ->visible(function(Get $get){
                            return $get('model_origen') == 'ConstructionCompanie' ? false:true;
                        })
                        ->live(),

                ])->columns(2),
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
                            // ->maxLength(255),
                        Forms\Components\Hidden::make('model')
                            ->default('Employee')
                            // ->maxLength(255),
                    ])
                    ->defaultItems(0)
                    ->columns(2)
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('work.name')
                    ->label(__("general.Work"))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dni')
                    ->label(__("general.DNI"))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__("general.FirstName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label(__("general.LastName"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__("general.Phone"))
                    // ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEmployees::route('/'),
        ];
    }
}
