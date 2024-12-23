<?php

namespace App\Filament\Resources\ConstructionCompanieResource\RelationManagers;

use App\Models\ConstructionCompanie;
use Filament\Forms;
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

                Forms\Components\Hidden::make('model_origen')
                    // ->label('Compañía de origen')
                    // ->options([
                    //     'ConstructionCompanie' => 'Compañías De Construcciones',
                    //     'Employee' => 'KM314'
                    // ])
                    ->default('ConstructionCompanie')

                    // ->dehydrated()
                    // ->visible(false)
                    // ->live()
                    ,

                Forms\Components\Hidden::make('model_origen_id')
                    ->label(__(''))
                    ->default(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()->id;
                    })
                    // ->options(function(){
                    //     return ConstructionCompanie::get()->pluck('name','id')->toArray();
                    // })
                    // ->disabled(function(Get $get){
                    //     return $get('model_origen') == 'ConstructionCompanie' ? false:true;
                    // })
                    // ->visible(function(Get $get){
                    //     return $get('model_origen') == 'ConstructionCompanie' ? true:false;
                    // })
                    // ->live()
                    ,
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
