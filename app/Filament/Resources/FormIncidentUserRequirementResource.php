<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormIncidentUserRequirementResource\Pages;
use App\Filament\Resources\FormIncidentUserRequirementResource\RelationManagers;
use App\Models\FormIncidentUserRequirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FormIncidentUserRequirementResource extends Resource
{
    protected static ?string $model = FormIncidentUserRequirement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Formularios Obligatorios';
    protected static ?string $label = 'Formulario Obligatorio';
    protected static ?string $navigationGroup = 'Formularios de Incidentes';

    public static function getPluralModelLabel(): string
    {
        return 'Formularios Obligatorios';
    }

    public static function getModelLabel(): string
    {
        return 'Formulario Obligatorio';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('form_incident_type_id')
                    ->label('Tipo de formulario')
                    ->relationship('formIncidentType', 'name')
                    ->required(),

                Forms\Components\Select::make('frequency')
                    ->label('Frecuencia')
                    ->options([
                        'daily' => 'Diaria',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                    ])
                    ->required()
                    ->live(),

                Forms\Components\TimePicker::make('deadline_time')
                    ->label('Hora límite')
                    ->required()
                    ->seconds(false)
                    ->helperText('Hora antes de la cual debe completarse el formulario'),

                Forms\Components\CheckboxList::make('days_of_week')
                    ->label('Días de la semana')
                    ->options([
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        7 => 'Domingo',
                    ])
                    ->visible(fn (Forms\Get $get) => $get('frequency') === 'weekly')
                    ->helperText('Solo para frecuencia semanal'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(3)
                    ->placeholder('Notas adicionales sobre este requerimiento'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('formIncidentType.name')
                    ->label('Tipo de formulario')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('frequency')
                    ->label('Frecuencia')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Diaria',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'success',
                        'weekly' => 'warning',
                        'monthly' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('deadline_time')
                    ->label('Hora límite')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Frecuencia')
                    ->options([
                        'daily' => 'Diaria',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ])
            ->emptyStateHeading('No hay formularios obligatorios configurados')
            ->emptyStateDescription('Configure formularios obligatorios para los usuarios.');
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
            'index' => Pages\ListFormIncidentUserRequirements::route('/'),
            'create' => Pages\CreateFormIncidentUserRequirement::route('/create'),
            'edit' => Pages\EditFormIncidentUserRequirement::route('/{record}/edit'),
        ];
    }
}
