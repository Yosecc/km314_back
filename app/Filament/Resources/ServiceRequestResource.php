<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceRequestResource\Pages;
use App\Models\CommonSpaces;
use App\Models\HomeInspection;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\RentalAttention;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\StartUp;
use App\Models\StartUpOption;
use App\Models\User;
use App\Models\WorksAndInstallation;
use Carbon\Carbon;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ServiceRequestResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = ServiceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Solicitudes de servicio';
    protected static ?string $label = 'solicitud de servicio';
    protected static ?string $navigationGroup = 'Servicios';
    protected static ?int $navigationSort = 1;

    public static function getPluralModelLabel(): string
    {
        return 'Solicitudes de servicio';
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view', 'view_any', 'create', 'update', 'restore', 'restore_any',
            'replicate', 'reorder', 'delete', 'delete_any', 'force_delete', 'force_delete_any',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(Auth::user());
    }

    public static function canEdit(Model $record): bool
    {
        if (! parent::canEdit($record)) {
            return false;
        }

        return ! static::isOwnerContext() || $record->isEditableByOwner();
    }

    public static function canManageNotes(): bool
    {
        return ! static::isOwnerContext()
            && (Auth::user()?->can('update_service::request') ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record)
            && (! static::isOwnerContext() || $record->isEditableByOwner());
    }

    public static function isOwnerContext(): bool
    {
        return (bool) (Auth::user()?->hasRole('owner') && Auth::user()?->owner_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la solicitud')
                ->description('Elegí el servicio y completá solamente la información necesaria para gestionarlo.')
                ->schema([
                    Hidden::make('user_id')->default(fn () => Auth::id()),
                    Hidden::make('model')->dehydrated(),
                    Hidden::make('is_calendar')->dehydrated(false),
                    Hidden::make('requires_start')->dehydrated(false),
                    Hidden::make('requires_end')->dehydrated(false),

                    Select::make('owner_id')
                        ->label('Propietario')
                        ->options(fn () => Owner::query()->orderBy('last_name')->get()->mapWithKeys(fn (Owner $owner) => [$owner->id => $owner->nombres()])->all())
                        ->default(fn () => Auth::user()?->owner_id)
                        ->live()
                        ->required()
                        ->disabled(fn () => static::isOwnerContext())
                        ->dehydrated(),

                    Select::make('lote_id')
                        ->label('Lote')
                        ->options(fn (Get $get) => Lote::query()->with('sector')->where('owner_id', $get('owner_id'))->get()->mapWithKeys(fn (Lote $lote) => [$lote->id => $lote->getNombre()])->all())
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get) => blank($get('owner_id'))),

                    Select::make('service_id')
                        ->label('Servicio solicitado')
                        ->options(fn () => Service::query()->where('status', true)->orderBy('order')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateHydrated(function (?string $state, Set $set): void {
                            static::hydrateServiceConfiguration($state, $set);
                        })
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            $service = $state ? Service::with('serviceRequestType')->find($state) : null;
                            static::hydrateServiceConfiguration($state, $set);
                            $set('model_id', null);
                            $set('options', []);
                            if ($service) {
                                $set('name', $service->name);
                            }
                        }),

                    Hidden::make('service_request_type_id')->required(),
                    Placeholder::make('service_request_type_preview')
                        ->label('Tipo de solicitud')
                        ->content(fn (Get $get) => ServiceRequestType::find($get('service_request_type_id'))?->name ?? 'Elegí un servicio')
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('name')
                        ->label('Asunto')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('model_id')
                        ->label(fn (Get $get) => match ($get('model')) {
                            'RentalAttention' => 'Atención de alquiler',
                            'HomeInspection' => 'Inspección',
                            'WorksAndInstallation' => 'Obra o instalación',
                            'CommonSpaces' => 'Espacio común',
                            'StartUp' => 'Startup',
                            default => 'Elemento asociado',
                        })
                        ->options(fn (Get $get) => static::modelOptions($get('model')))
                        ->searchable()
                        ->visible(fn (Get $get) => filled($get('model')))
                        ->required(fn (Get $get) => filled($get('model'))),

                    Select::make('options')
                        ->label('Opciones')
                        ->multiple()
                        ->searchable()
                        ->options(fn () => StartUpOption::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->visible(fn (Get $get) => $get('model') === 'StartUp'),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Fecha y hora de inicio')
                        ->seconds(false)
                        ->default(now())
                        ->visible(fn (Get $get) => (bool) $get('requires_start'))
                        ->required(fn (Get $get) => (bool) $get('requires_start'))
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            if ($state && $get('is_calendar') && ! $get('ends_at')) {
                                $set('ends_at', Carbon::parse($state)->addHour()->format('Y-m-d H:i:s'));
                            }
                        }),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Fecha y hora de finalización')
                        ->seconds(false)
                        ->visible(fn (Get $get) => (bool) $get('requires_end'))
                        ->required(fn (Get $get) => (bool) $get('requires_end'))
                        ->after('starts_at'),

                    Forms\Components\Textarea::make('observations')
                        ->label('Observaciones para administración')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Fieldset::make('responsible')
                ->label('Responsable en el espacio común')
                ->relationship('responsible')
                ->visible(fn (Get $get) => $get('model') === 'CommonSpaces')
                ->schema([
                    Forms\Components\TextInput::make('dni')->label('DNI')->numeric()->required(),
                    Forms\Components\TextInput::make('first_name')->label('Nombre')->required()->maxLength(255),
                    Forms\Components\TextInput::make('last_name')->label('Apellido')->required()->maxLength(255),
                    Forms\Components\TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                ])->columns(2),

            Forms\Components\Section::make('Documentos adjuntos')
                ->schema([
                    Repeater::make('serviceRequestFile')
                        ->relationship()
                        ->label('Archivos')
                        ->addActionLabel('Adjuntar archivo')
                        ->schema([
                            Hidden::make('user_id')->default(fn () => Auth::id()),
                            Forms\Components\TextInput::make('description')->label('Descripción')->maxLength(255),
                            Forms\Components\FileUpload::make('file')
                                ->label('Archivo')
                                ->disk('public')
                                ->directory('service-requests')
                                ->visibility('public')
                                ->storeFileNamesIn('attachment_file_names')
                                ->openable()
                                ->downloadable(),
                        ])->defaultItems(0)->columns(2),
                ]),

            Forms\Components\Section::make('Notas de seguimiento')
                ->schema([
                    Repeater::make('serviceRequestNote')
                        ->relationship()
                        ->label('Notas')
                        ->addActionLabel('Agregar nota')
                        ->addable(fn (): bool => static::canManageNotes())
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            Hidden::make('id'),
                            Hidden::make('user_id')->default(fn () => Auth::id()),
                            Forms\Components\Textarea::make('description')
                                ->label('Nota')
                                ->required()
                                ->rows(3)
                                ->disabled(fn (Get $get): bool => ! static::canManageNotes() || filled($get('id')))
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(0)
                        ->itemLabel(fn (array $state): ?string => filled($state['description'] ?? null) ? str($state['description'])->limit(60)->toString() : 'Nueva nota'),
                ]),

            Forms\Components\Section::make('Gestión interna')
                ->visible(fn () => ! static::isOwnerContext())
                ->schema([
                    Select::make('service_request_status_id')
                        ->label('Estado')
                        ->options(fn () => ServiceRequestStatus::options())
                        ->default(fn () => ServiceRequestStatus::defaultPending()->id)
                        ->required(),
                    Select::make('asignado_status_id')
                        ->label('Usuario asignado')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Forms\Components\Textarea::make('resolution_notes')
                        ->label('Nota de gestión o resolución')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function modelOptions(?string $model): array
    {
        return match ($model) {
            'RentalAttention' => RentalAttention::query()->orderBy('name')->pluck('name', 'id')->all(),
            'HomeInspection' => HomeInspection::query()->orderBy('name')->pluck('name', 'id')->all(),
            'WorksAndInstallation' => WorksAndInstallation::query()->orderBy('name')->pluck('name', 'id')->all(),
            'CommonSpaces' => CommonSpaces::query()->orderBy('name')->pluck('name', 'id')->all(),
            'StartUp' => StartUp::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }

    public static function hydrateServiceConfiguration(?string $serviceId, Set $set): void
    {
        $service = $serviceId ? Service::with('serviceRequestType')->find($serviceId) : null;
        $set('service_request_type_id', $service?->service_request_type_id);
        $set('model', $service?->model);
        $set('is_calendar', (bool) $service?->serviceRequestType?->isCalendar);
        $set('requires_start', (bool) $service?->isDateInicio);
        $set('requires_end', (bool) $service?->isDateFin);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('serviceRequestStatus.color')->label(''),
                Tables\Columns\TextColumn::make('lote.lote_id')->label('Lote')->formatStateUsing(fn (ServiceRequest $record) => $record->lote?->getNombre() ?? 'Sin lote')->badge(),
                Tables\Columns\TextColumn::make('name')->label('Solicitud')->searchable()->description(fn (ServiceRequest $record) => $record->service?->name),
                Tables\Columns\TextColumn::make('owner.first_name')->label('Propietario')->formatStateUsing(fn (ServiceRequest $record) => $record->owner?->nombres() ?? 'Sin propietario')->toggleable(isToggledHiddenByDefault: static::isOwnerContext()),
                Tables\Columns\TextColumn::make('serviceRequestStatus.name')->label('Estado')->badge()->color(fn (ServiceRequest $record) => static::statusColor($record->statusCode())),
                Tables\Columns\TextColumn::make('starts_at')->label('Programada')->dateTime('d/m/Y H:i')->placeholder('Sin fecha')->sortable(),
                Tables\Columns\TextColumn::make('userAsignado.name')->label('Asignado a')->placeholder('Sin asignar')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creada')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_request_status_id')->label('Estado')->options(fn () => ServiceRequestStatus::options()),
                Tables\Filters\SelectFilter::make('service_id')->label('Servicio')->relationship('service', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn (ServiceRequest $record): bool => static::canView($record)),
                Tables\Actions\EditAction::make()->visible(fn (ServiceRequest $record) => static::canEdit($record)),
                static::changeStatusAction(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function changeStatusAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('changeStatus')
            ->label('Actualizar estado')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->visible(fn (ServiceRequest $record) => ! static::isOwnerContext() && Auth::user()->can('update', $record))
            ->form([
                Select::make('service_request_status_id')->label('Nuevo estado')->options(fn () => ServiceRequestStatus::options())->required(),
                Select::make('asignado_status_id')->label('Asignar a')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                Forms\Components\Textarea::make('resolution_notes')->label('Nota para el propietario')->rows(3),
            ])
            ->fillForm(fn (ServiceRequest $record) => [
                'service_request_status_id' => $record->service_request_status_id,
                'asignado_status_id' => $record->asignado_status_id,
                'resolution_notes' => $record->resolution_notes,
            ])
            ->action(function (ServiceRequest $record, array $data): void {
                abort_unless(Auth::user()->can('update', $record), 403);
                $status = ServiceRequestStatus::findOrFail($data['service_request_status_id']);
                $record->fill(['asignado_status_id' => $data['asignado_status_id'] ?? null]);
                $record->transitionTo($status, Auth::user(), $data['resolution_notes'] ?? null);
                Notification::make()->title('Estado actualizado')->success()->send();
            });
    }

    public static function statusColor(?string $code): string
    {
        return match ($code) {
            ServiceRequestStatus::PENDING => 'warning',
            ServiceRequestStatus::IN_PROGRESS => 'info',
            ServiceRequestStatus::COMPLETED => 'success',
            ServiceRequestStatus::REJECTED => 'danger',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'create' => Pages\CreateServiceRequest::route('/create'),
            'view' => Pages\ViewServiceRequest::route('/{record}'),
            'edit' => Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
