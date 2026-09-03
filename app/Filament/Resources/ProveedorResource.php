<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Filament\Resources\ProveedorResource\RelationManagers;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'proveedor';
    protected static ?string $pluralModelLabel = 'proveedores';
    protected static ?string $slug = 'proveedores';

    private static function documentosVehiculo(): array
    {
        return [
            ['name' => 'Seguro del Vehículo'],
            ['name' => 'VTV'],
            ['name' => 'Cédula del Vehículo'],
        ];
    }

    private static function esquemaVehiculo(): array
    {
        return [
            Forms\Components\Hidden::make('id'),
            Forms\Components\TextInput::make('marca')
                ->label('Marca')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('modelo')
                ->label('Modelo')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('color')
                ->label('Color')
                ->maxLength(255),
            Forms\Components\TextInput::make('patente')
                ->label('Patente')
                ->required()
                ->maxLength(255),
            Forms\Components\Repeater::make('files')
                ->label('Documentos del vehículo')
                ->relationship()
                ->schema([
                    Forms\Components\Hidden::make('id'),
                    Forms\Components\Hidden::make('name'),
                    Forms\Components\DatePicker::make('fecha_vencimiento')
                        ->label('Vencimiento')
                        ->required(),
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo')
                        ->required()
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(
                            fn ($file, $record) => $file ? $file->getClientOriginalName() : $record?->file
                        ),
                ])
                ->default(self::documentosVehiculo())
                ->afterStateHydrated(function (Forms\Components\Repeater $component, $state): void {
                    if (empty($state)) {
                        $component->state(self::documentosVehiculo());
                    }
                })
                ->minItems(3)
                ->maxItems(3)
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->itemLabel(fn (array $state): string => $state['name'] ?? 'Documento')
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la empresa')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_empresa')
                            ->label('Nombre de la empresa')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono_empresa')
                            ->label('Teléfono de la empresa')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cuit_empresa')
                            ->label('CUIT de la empresa')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nombre_responsable')
                            ->label('Nombre del responsable')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('telefono_responsable')
                            ->label('Teléfono del responsable')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('status')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Vehículos')
                    ->description('Los vehículos y su documentación son opcionales.')
                    ->schema([
                        Forms\Components\Repeater::make('autos')
                            ->label('Vehículos')
                            ->relationship()
                            ->schema(self::esquemaVehiculo())
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['model'] = 'Proveedor';
                                $data['user_id'] = Auth::id();

                                return $data;
                            })
                            ->itemLabel(fn (array $state): string => isset($state['patente'])
                                ? "Vehículo: {$state['patente']}"
                                : 'Nuevo vehículo')
                            ->addActionLabel('Agregar vehículo')
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cuit_empresa')
                    ->label('CUIT')
                    ->searchable()
                    ->placeholder('Sin informar'),
                Tables\Columns\TextColumn::make('telefono_empresa')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_responsable')
                    ->label('Responsable')
                    ->searchable()
                    ->placeholder('Sin informar'),
                Tables\Columns\TextColumn::make('empleados_count')
                    ->label('Empleados')
                    ->counts('empleados')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Estado activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nombre_empresa');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmpleadosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProveedores::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
