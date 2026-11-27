<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterestedStatusResource\Pages;
use App\Models\InterestedStatus;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InterestedStatusResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = InterestedStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?string $navigationLabel = 'Estados comerciales';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'estado comercial';

    public static function getPermissionPrefixes(): array
    {
        return ['view', 'view_any', 'create', 'update', 'delete', 'delete_any'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\Select::make('color')->label('Color')->required()->options([
                'gray' => 'Gris', 'info' => 'Celeste', 'primary' => 'Azul', 'warning' => 'Amarillo',
                'success' => 'Verde', 'danger' => 'Rojo',
            ]),
            Forms\Components\TextInput::make('sort_order')->label('Orden')->numeric()->default(0)->required(),
            Forms\Components\Toggle::make('is_closed')->label('Estado final')->helperText('Los estados finales no requieren un próximo seguimiento.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->badge()->color(fn (InterestedStatus $record) => $record->color)->searchable(),
            Tables\Columns\TextColumn::make('sort_order')->label('Orden')->sortable(),
            Tables\Columns\IconColumn::make('is_closed')->label('Final')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInterestedStatuses::route('/')];
    }
}
