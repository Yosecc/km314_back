<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Servicios';
    protected static ?string $label = 'servicio';
    protected static ?string $navigationGroup = 'Servicios - Configuración';


    public static function getPluralModelLabel(): string
    {
        return 'Servicios';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('service_type_id')
                    ->label('Tipo de servicio')
                    ->required()
                    ->relationship(name: 'serviceType', titleAttribute: 'name'),

                Forms\Components\Toggle::make('status')
                    ->label('Activo')
                    ->default(true),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('color')
                    ->type('color'),
                Forms\Components\TextInput::make('amount')
                    ->label('Precio')
                    ->maxLength(255),
                Forms\Components\Hidden::make('model'),
                Forms\Components\Select::make('service_request_type_id')
                    ->label('Tipo de solicitud ')
                    ->relationship(name: 'serviceRequestType', titleAttribute: 'name'),
                Forms\Components\Toggle::make('isDateInicio')
                    ->label('Solicitar fecha de inicio')
                    ->default(true),
                Forms\Components\Toggle::make('isDateFin')
                    ->label('Solicitar fecha de Fin')
                    ->default(false),
                RichEditor::make('terminos')->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextInputColumn::make('order')
                    ->label('Orden')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color'),
                // Tables\Columns\TextColumn::make('serviceRequestType.name')
                //     ->label('Tipo de Solicitud')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('serviceType.order')
                    ->label('Orden según tipo de servicio')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Tipo de Servicio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('amount')->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('serviceType.order', 'asc')

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('moveUp')
                    ->label('Subir')
                    ->icon('heroicon-o-arrow-up')
                    ->action(function ($record) {
                        $previous = $record->where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();

                        if ($previous) {
                            $currentOrder = $record->order;
                            $record->update(['order' => $previous->order]);
                            $previous->update(['order' => $currentOrder]);
                        }
                    })
                    ->visible(fn ($record) => $record->order > 1), // Oculta si es el primer registro
                Action::make('moveDown')
                    ->label('Bajar')
                    ->icon('heroicon-o-arrow-down')
                    ->action(function ($record) {
                        $next = $record->where('order', '>', $record->order)
                            ->orderBy('order', 'asc')
                            ->first();

                        if ($next) {
                            $currentOrder = $record->order;
                            $record->update(['order' => $next->order]);
                            $next->update(['order' => $currentOrder]);
                        }
                    }),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
