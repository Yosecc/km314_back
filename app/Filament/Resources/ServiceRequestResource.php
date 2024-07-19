<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Lote;
use Filament\Tables;
use App\Models\Owner;
use App\Models\Property;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServiceRequest;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Filament\Resources\ServiceRequestResource\RelationManagers;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $label = 'solicitud';
    protected static ?string $navigationGroup = 'Solicitudes';

    
    public static function getPluralModelLabel(): string
    {
        return 'Solicitudes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_request_status_id')
                    // ->label(__("general.LoteStatus"))
                    ->required()
                    ->relationship(name: 'serviceRequestStatus', titleAttribute: 'name'),

                Forms\Components\Select::make('service_request_type_id')
                    // ->label(__("general.LoteStatus"))
                    ->required()
                    ->relationship(name: 'serviceRequestType', titleAttribute: 'name'),

                Forms\Components\Select::make('service_id')
                    // ->label(__("general.LoteStatus"))
                    ->required()
                    ->relationship(name: 'service', titleAttribute: 'name'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->required(),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->required(),

                Forms\Components\Select::make('owner_id')->label(__("general.Owner"))    
                    ->relationship(name: 'owner')
                    ->getOptionLabelFromRecordUsing(fn (Owner $record) => "{$record->first_name} {$record->last_name}"),

                Forms\Components\Select::make('lote_id')
                    ->label(__("general.Lotes"))
                    ->options(Lote::get()->map(function($lote){
                        $lote['lote_name'] = $lote->sector->name . $lote->lote_id;
                        return $lote;
                    })
                    ->pluck('lote_name', 'id')->toArray()),

                Forms\Components\Select::make('propertie_id')
                    ->label(__("general.Propertie"))
                    ->options(Property::get()->pluck('identificador', 'id')->toArray()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                Tables\Columns\ColorColumn::make('serviceRequestStatus.color')
                ->label(''),
                Tables\Columns\TextColumn::make('serviceRequestStatus.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceRequestType.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->searchable(),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
