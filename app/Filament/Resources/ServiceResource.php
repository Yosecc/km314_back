<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Actions\Action;
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
                    // ->label(__("general.LoteStatus"))
                    ->required()
                    ->relationship(name: 'serviceType', titleAttribute: 'name'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('color')->type('color'),
                Forms\Components\TextInput::make('amount')->maxLength(255),
                Forms\Components\TextInput::make('model')->maxLength(255),
                Forms\Components\Select::make('service_request_type_id')->relationship(name: 'serviceRequestType', titleAttribute: 'name'),

                RichEditor::make('terminos')->columnSpanFull()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                ->label('Orden')
                ->sortable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('serviceRequestType.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
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
            ->defaultSort('order', 'asc')

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
