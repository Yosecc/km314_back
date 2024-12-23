<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConstructionResource\Pages;
use App\Filament\Resources\ConstructionResource\RelationManagers;
use App\Models\Construction;
use App\Models\Lote;
use App\Models\loteStatus;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as ActionNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConstructionResource extends Resource
{
    protected static ?string $model = Construction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Construcciones';
    protected static ?string $label = 'construcción';
    // protected static ?string $navigationGroup = 'Configuracion de construcciones';


    public static function getPluralModelLabel(): string
    {
        return 'construcciones';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('construction_type_id')
                    ->relationship(name: 'constructionType', titleAttribute: 'name')
                    ->label(__("general.tipo_construccion"))
                    ->required(),
                Forms\Components\Select::make('construction_companie_id')
                    ->relationship(name: 'constructionCompanie', titleAttribute: 'name')
                    ->label(__("general.conpanie_construccion"))
                    ->required(),
                Forms\Components\Select::make('construction_status_id')
                    ->required()
                    ->relationship(name: 'constructionStatus', titleAttribute: 'name')
                    ->label(__("general.status_construccion")),

                Forms\Components\Select::make('lote_id')
                    // ->relationship(name: 'lote')
                    ->options(function(){
                        return Lote::get()->map(function($lote){
                            $lote['lote_name'] = $lote->getNombre();
                            return $lote;
                        })->pluck('lote_name', 'id')->toArray();
                    })
                    // ->getOptionLabelFromRecordUsing(fn (Lote $record) => "{$record->getNombre()}")
                    ->required()
                    ->searchable()
                    ->label(__("general.Lote")),
                Forms\Components\Select::make('owner_id')
                    ->required()
                    // ->relationship(name: 'owner')
                    ->options(function(){
                        return Owner::get()->map(function($lote){
                            $lote['name'] = $lote->nombres();
                            return $lote;
                        })->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    // ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->nombres()}")
                    ->label(__("general.Owner")),

                Forms\Components\TextInput::make('width')
                    ->label(__("general.Width"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('height')
                    ->label(__("general.Height"))
                    ->maxLength(255),
                Forms\Components\TextInput::make('m2')
                    ->label(__("general.M2"))
                    ->maxLength(255),
                Forms\Components\Textarea::make('observations')
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->label(__('general.Observations')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('constructionStatus.color')->label(__("general.Color")),
                Tables\Columns\TextColumn::make('constructionType.name')
                    ->label(__("general.tipo_construccion"))
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('constructionCompanie.name')
                    ->label(__("general.conpanie_construccion"))
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('constructionStatus.name')
                    ->label(__("general.status_construccion"))
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lote')
                    ->label(__("general.Lote"))
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (Lote $state) => "{$state->getNombre()}" )
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner')
                ->label(__('Propietario'))
                    ->searchable()
                    ->formatStateUsing(fn (Owner $state) => "{$state->nombres()}" )
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__("general.deleted_at"))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__("general.created_at"))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),




            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Action::make('finish')
                    ->label('Finalizar')
                    ->color('warning')
                    ->accessSelectedRecords()
                    ->modalHeading('Finalizar construcción')
                    ->modalDescription('Al finalizar la construcción, este registro se convertirá en una "Propiedad" activa con toda su información actual. Complete los siguientes campos para continuar.')
                    // ->modalSubmitActionLabel('Finalizar')
                    ->fillForm(fn (Construction $record): array => [
                        'owner_id' => $record->owner_id,
                    ])
                    ->form([
                        Forms\Components\Select::make('property_type_id')
                            ->label(__("general.PropertyType"))
                            ->options(PropertyType::query()->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Select::make('owner_id')->label(__("general.Owner"))
                            ->required()
                            ->options(Owner::get()->map(function($owner){
                                $owner['nam'] =  $owner->first_name .' '.$owner->last_name ;
                                return $owner;
                            })->pluck('nam', 'id')->toArray())
                    ])
                ->action(function (Construction $record,  array $data ) {

                    Property::insert([
                        'property_type_id' => $data['property_type_id'],
                        'owner_id' => $data['owner_id'],
                        'width' => $record->width,
                        'height' => $record->height,
                        'm2' => $record->m2,
                    ]);

                    $record->delete();

                    Notification::make()
                        ->title('Construcción finalizada')
                        ->success()
                        ->actions([
                            ActionNotification::make('view')
                                ->label('Ver propiedades')
                                ->button()
                                ->url('/properties')
                        ])
                        ->send();
                })

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageConstructions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
