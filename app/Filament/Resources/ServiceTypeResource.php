<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTypeResource\Pages;
use App\Filament\Resources\ServiceTypeResource\RelationManagers;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Tipos de servicios';
    protected static ?string $label = 'tipo de servicio';
    protected static ?string $navigationGroup = 'Servicios - Configuración';


    public static function getPluralModelLabel(): string
    {
        return 'Tipo de servicios';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                    Forms\Components\Toggle::make('status')
                        ->label('Activo')
                        ->default(true),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                ->label('Orden')
                ->sortable(),
                Tables\Columns\TextColumn::make('name')
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

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServiceTypes::route('/'),
        ];
    }
}
