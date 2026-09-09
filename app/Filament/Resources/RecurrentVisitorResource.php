<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurrentVisitorResource\Pages;
use App\Models\Owner;
use App\Models\RecurrentVisitor;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecurrentVisitorResource extends Resource
{
    protected static ?string $model = RecurrentVisitor::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Gestión de visitantes recurrentes';
    protected static ?string $label = 'visitante recurrente';
    // protected static ?string $navigationGroup = 'Control de acceso';

    public static function canViewAny(): bool
    {
        if (Auth::user()->hasRole('owner')) {
            return (bool) Auth::user()->is_terms_condition;
        }

        return Auth::user()->hasAnyRole(['super_admin', 'admin']) || Auth::user()->can('view_any_recurrent_visitor');
    }

    private static function vehicleSchema(): array
    {
        return [
            Forms\Components\Hidden::make('id'),
            Forms\Components\TextInput::make('marca')->label('Marca')->required(),
            Forms\Components\TextInput::make('modelo')->label('Modelo')->required(),
            Forms\Components\TextInput::make('patente')->label('Patente')->required(),
            Forms\Components\TextInput::make('color')->label('Color'),
            Forms\Components\Repeater::make('files')
                ->label('Documentos del vehículo')
                ->relationship()
                ->schema([
                    Forms\Components\Hidden::make('id'),
                    Forms\Components\Hidden::make('name'),
                    Forms\Components\DatePicker::make('fecha_vencimiento')->label('Vencimiento')->required(),
                    Forms\Components\FileUpload::make('file')
                        ->label('Archivo')
                        ->required()
                        ->openable()
                        ->getUploadedFileNameForStorageUsing(fn ($file, $record) => $file ? $file->getClientOriginalName() : $record?->file),
                ])
                ->default([
                    ['name' => 'Seguro del Vehículo'],
                    ['name' => 'VTV'],
                    ['name' => 'Cédula del Vehículo'],
                ])
                ->afterStateHydrated(function (Forms\Components\Repeater $component, $state) {
                    if (empty($state)) {
                        $component->state([
                            ['name' => 'Seguro del Vehículo'],
                            ['name' => 'VTV'],
                            ['name' => 'Cédula del Vehículo'],
                        ]);
                    }
                })
                ->minItems(3)->maxItems(3)->addable(false)->deletable(false)
                ->itemLabel(fn (array $state) => $state['name'] ?? 'Documento'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('user_id')->default(fn () => Auth::id()),
            Forms\Components\Hidden::make('status')->default(fn () => Auth::user()->hasRole('owner') ? 'pendiente' : 'aprobado'),
            Forms\Components\Select::make('owner_id')
                ->label('Propietario')->relationship('owner', 'first_name')
                ->getOptionLabelFromRecordUsing(fn (Owner $record) => $record->nombres())
                ->default(fn () => Auth::user()->owner_id)
                ->disabled(fn () => Auth::user()->hasRole('owner'))
                ->dehydrated()
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('dni')->label('DNI')->numeric()->required(),
            Forms\Components\TextInput::make('first_name')->label('Nombre')->required(),
            Forms\Components\TextInput::make('last_name')->label('Apellido')->required(),
            Forms\Components\TextInput::make('phone')->label('Teléfono')->tel(),
            Forms\Components\FileUpload::make('identity_document')
                ->label('Documento de identidad')
                ->required(fn (string $operation) => $operation === 'create')
                ->openable()
                ->downloadable()
                ->getUploadedFileNameForStorageUsing(fn ($file, $record) => $file ? $file->getClientOriginalName() : $record?->identity_document),
            Forms\Components\Textarea::make('observations')->label('Observaciones')->columnSpanFull(),
            Forms\Components\Repeater::make('autos')
                ->label('Vehículos')->relationship()->schema(self::vehicleSchema())
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                    $data['model'] = 'RecurrentVisitor';
                    $data['user_id'] = Auth::id();

                    return $data;
                })
                ->itemLabel(fn (array $state) => isset($state['patente']) ? "Vehículo: {$state['patente']}" : 'Nuevo vehículo')
                ->addActionLabel('Agregar vehículo')->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query) {
            if (Auth::user()->hasRole('owner')) {
                $query->where('owner_id', Auth::user()->owner_id);
            }
        })->columns([
            Tables\Columns\TextColumn::make('dni')->label('DNI')->searchable(),
            Tables\Columns\TextColumn::make('first_name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('last_name')->label('Apellido')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge()->label('Estado')
                ->color(fn (string $state) => ['pendiente' => 'warning', 'aprobado' => 'success', 'rechazado' => 'danger'][$state]),
            Tables\Columns\TextColumn::make('documentos_vehiculo')
                ->label('Documentación')
                ->getStateUsing(fn (RecurrentVisitor $record) => $record->vencidosAutosFile()
                    ? 'Documentos de vehículo vencidos: ' . implode(', ', $record->vencidosAutosFile())
                    : 'Al día')
                ->color(fn (RecurrentVisitor $record) => $record->vencidosAutosFile() ? 'danger' : 'success')
                ->wrap(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Registrado'),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobado' => 'Aprobado',
                    'rechazado' => 'Rechazado',
                ]),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->after(function (RecurrentVisitor $record) {
                    if (Auth::user()->hasRole('owner')) {
                        $record->update(['status' => 'pendiente']);
                    }
                }),
            Tables\Actions\Action::make('aprobar')->label('Aprobar')->icon('heroicon-o-check-circle')->color('success')
                ->requiresConfirmation()->visible(fn (RecurrentVisitor $record) => Auth::user()->hasAnyRole(['admin', 'super_admin']) && $record->status === 'pendiente')
                ->action(fn (RecurrentVisitor $record) => $record->update(['status' => 'aprobado'])),
            Tables\Actions\Action::make('rechazar')->label('Rechazar')->icon('heroicon-o-x-circle')->color('danger')
                ->requiresConfirmation()->visible(fn (RecurrentVisitor $record) => Auth::user()->hasAnyRole(['admin', 'super_admin']) && $record->status === 'pendiente')
                ->action(fn (RecurrentVisitor $record) => $record->update(['status' => 'rechazado'])),
            Tables\Actions\DeleteAction::make(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRecurrentVisitors::route('/')];
    }
}
