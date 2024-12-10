<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConstructionCompanieResource\Pages;
use App\Filament\Resources\ConstructionCompanieResource\RelationManagers;
use App\Models\ConstructionCompanie;
use App\Models\Lote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
class ConstructionCompanieResource extends Resource
{
    protected static ?string $model = ConstructionCompanie::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Compañías de construcciones';
    protected static ?string $label = 'compañía de construcción';
    protected static ?string $navigationGroup = 'Construcciones - Configuración';


    public static function getPluralModelLabel(): string
    {
        return 'compañías de construcciones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                ->label(__("general.Name"))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                ->label(__("general.Phone"))
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('lote_id')->label(__("general.Lote"))
                    ->live()
                    // ->relationship(name: 'lote')
                    ->options(function(Get $get){
                        $lotes = Lote::get();
                        $lotes->map(function($lote){
                            $lote['texto'] = $lote->sector->name.$lote->lote_id;
                            return $lote;
                        });
                        return $lotes->pluck('texto','id')->toArray();
					}),

                    Repeater::make('empleados')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('first_name')->required(),
                            // Forms\Components\Select::make('role')
                            //     ->options([
                            //         'member' => 'Member',
                            //         'administrator' => 'Administrator',
                            //         'owner' => 'Owner',
                            //     ])
                            //     ->required(),
                        ])
                        ->columns(2)

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                ->label(__("general.Name"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                ->label(__("general.Phone"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('lote')
                    ->label(__("general.Lote"))
                    ->formatStateUsing(fn (Lote $state) => "{$state->sector->name}{$state->lote_id}" )
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                ->label(__("general.created_at"))
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
            'index' => Pages\ManageConstructionCompanies::route('/'),
        ];
    }
}
