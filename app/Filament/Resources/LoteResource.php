<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoteResource\Pages;
use App\Filament\Resources\LoteResource\RelationManagers;
use App\Models\Lote;
use App\Models\Owner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
class LoteResource extends Resource
{
    protected static ?string $model = Lote::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Lotes';
    protected static ?string $label = 'lote';
    // protected static ?string $navigationGroup = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('frente')
                    ->label(__("Frente"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('contrafrente')
                    ->label(__("Contrafrente"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('lado_uno')
                    ->label(__("Lado uno"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('lado_dos')
                    ->label(__("lado dos"))
                    ->maxLength(255),

                Forms\Components\TextInput::make('width')
                    ->label(__("general.Width"))
                    ->maxLength(255),

                Forms\Components\TextInput::make('height')
                    ->label(__("general.Long"))
                    ->maxLength(255),

                Forms\Components\TextInput::make('m2')
                    ->label(__("general.M2"))
                    ->maxLength(255),

                Forms\Components\Select::make('sector_id')
                    ->label(__("general.Sector"))
                    ->required()
                    ->relationship(name: 'sector', titleAttribute: 'name'),

                Forms\Components\Select::make('lote_id')
                    ->label(__("general.LoteID"))
                    ->options(function(){
                        return Lote::get()->map(function($lote){
                            $lote['lote_name'] = $lote->getNombre();
                            return $lote;
                        })->pluck('lote_name', 'id')->toArray();
                    })
                    ->searchable()
                    ->required()
                    ,

                Forms\Components\Select::make('lote_type_id')
                    ->label(__("general.LoteType"))
                    ->relationship(name: 'loteType', titleAttribute: 'name'),

                Forms\Components\Select::make('lote_status_id')
                    ->label(__("general.LoteStatus"))
                    ->relationship(name: 'loteStatus', titleAttribute: 'name'),

                Forms\Components\Select::make('owner_id')
                    ->label(__("general.Owner"))
                    ->options(function(){
                        return Owner::get()->map(function($lote){
                            $lote['name'] = $lote->nombres();
                            return $lote;
                        })->pluck('name', 'id')->toArray();
                    })
                    ->searchable(),

                Forms\Components\TextInput::make('ubication')
                    ->label(__("Observaciones"))
                    ->columnSpanFull(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('loteStatus.color')->label(__("general.Status")),
                Tables\Columns\TextColumn::make('sector.name')->label(__("general.Sector"))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lote_id')->label(__("general.LoteID"))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loteStatus.name')->label(__("general.LoteStatus"))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loteType.name')->label(__("general.LoteType"))
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner')
                    ->label(__("general.Owner"))
                    ->formatStateUsing(fn (Owner $state) => "{$state->nombres()}" )
                    ->sortable(),

                Tables\Columns\TextColumn::make('owner.phone')
                    ->label(__("Teléfono"))
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('loteStatus')
                        ->label(__('Estado del lote'))
                        ->relationship('loteStatus', 'name'),

                SelectFilter::make('owner')
                    ->label(__('Propietario'))
                    ->relationship('owner', 'first_name'),
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
            'index' => Pages\ManageLotes::route('/'),
        ];
    }
}
