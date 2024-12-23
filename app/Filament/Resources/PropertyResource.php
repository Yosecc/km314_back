<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Lote;

use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PropertyResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PropertyResource\RelationManagers;
use Filament\Forms\Get;
class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Propiedades';
    protected static ?string $label = 'propiedad';

    public static function getPluralModelLabel(): string
    {
        return 'propiedades';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('identificador')->label(__("general.identificador"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('width')->label(__("general.Width"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('height')->label(__("general.Height"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('m2')->label(__("general.M2"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cuentamunicipal')
                    ->label(__("Cuenta Municipal"))
                    ->maxLength(255),


                Forms\Components\Select::make('property_type_id')->label(__("general.PropertyType"))
                    ->required()
                    ->relationship(name: 'propertyType', titleAttribute: 'name'),

                Forms\Components\Select::make('owner_id')
                    ->required()
                    ->options(function(){
                        return Owner::get()->map(function($lote){
                            $lote['name'] = $lote->nombres();
                            return $lote;
                        })->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->label(__("general.Owner")),

                Forms\Components\Select::make('lote_id')->label(__("general.Lote"))
                    ->required()
                    ->live()
                    // ->relationship(name: 'lote')
                    ->options(function(Get $get){

					    if(!$get('owner_id')){
                            return [];
                        }
                            //dd($get('owner_id'));
                            $lotes = Lote::where('owner_id',$get('owner_id'))->get();
                            //$lotes = Lote::get();
                            $lotes->map(function($lote){
                                $lote['texto'] = $lote->sector->name.$lote->lote_id;
                                return $lote;
                            });
                            return $lotes->pluck('texto','id')->toArray();
					})
                    // ->getOptionLabelFromRecordUsing(fn (Lote $record) => "{$record->first_name} {$record->last_name}"),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('identificador')
                    ->label(__("general.identificador"))
                    ->searchable()
                    ->sortable(),

                 Tables\Columns\TextColumn::make('propertyType.name')->label('Tipo de propiedad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner')->label('Propietario')
                    ->formatStateUsing(fn (Owner $state) => "{$state->nombres()}" )

                    ->sortable(),

                // Tables\Columns\TextColumn::make('width')
                    // ->label(__("general.Width"))
                    // ->searchable(),
                // Tables\Columns\TextColumn::make('height')
                    // ->label(__("general.Height"))
                    // ->searchable(),
                // Tables\Columns\TextColumn::make('m2')
                    // ->label(__("general.M2"))
                    // ->searchable(),
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
            'index' => Pages\ManageProperties::route('/'),
        ];
    }
}
