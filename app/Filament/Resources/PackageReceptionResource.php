<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageReceptionResource\Pages;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\PackageReception;
use App\Models\PackageReceptionFile;
use App\Services\PackageReceptionService;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PackageReceptionResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = PackageReception::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Recepción de paquetes';
    protected static ?string $modelLabel = 'recepción de paquete';
    protected static ?string $pluralModelLabel = 'recepciones de paquetes';
    protected static ?string $navigationGroup = 'Recepción';
    protected static ?int $navigationSort = 1;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'receive',
            'deliver',
            'cancel',
            'view_sensitive',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(Auth::user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Destino y correo')->schema([
                Forms\Components\Select::make('owner_id')->label('Propietario')
                    ->options(fn () => Owner::query()->orderBy('first_name')->get()->mapWithKeys(fn (Owner $o) => [$o->id => $o->nombres()]))
                    ->default(fn () => Auth::user()->owner_id)->disabled(fn () => Auth::user()->hasRole('owner'))->dehydrated()
                    ->searchable()->preload()->live()->required(),
                Forms\Components\Select::make('lote_id')->label('Lote')
                    ->options(fn (Get $get) => Lote::query()->where('owner_id', $get('owner_id'))->get()->mapWithKeys(fn (Lote $l) => [$l->id => $l->getNombre()]))
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('courier_name')->label('Correo o empresa de envío')->placeholder('Mercado Libre, OCA, Andreani…')->required()->maxLength(255),
                Forms\Components\TextInput::make('tracking_number')->label('Número de seguimiento')->maxLength(255),
            ])->columns(2),
            Forms\Components\Section::make('Horario esperado')->schema([
                Forms\Components\DatePicker::make('expected_date')->label('Fecha')->native(false)->required(),
                Forms\Components\TimePicker::make('expected_from_time')->label('Hora desde')->seconds(false)->required(),
                Forms\Components\TimePicker::make('expected_until_time')->label('Hora hasta')->seconds(false)->afterOrEqual('expected_from_time')->required(),
            ])->columns(3),
            Forms\Components\Section::make('Datos opcionales')->schema([
                Forms\Components\TextInput::make('carrier_access_code')->label('Código o palabra clave')->maxLength(1000),
                Forms\Components\TextInput::make('recipient_name')->label('Nombre de quien hizo la compra'),
                Forms\Components\TextInput::make('recipient_dni')->label('DNI'),
                Forms\Components\TextInput::make('recipient_phone')->label('Teléfono')->tel(),
                Forms\Components\TextInput::make('expected_packages_count')->label('Cantidad de bultos esperada')->numeric()->minValue(1),
                Forms\Components\FileUpload::make('registration_uploads')->label('Imágenes o comprobantes')->multiple()
                    ->disk('local')->directory('package-receptions/registration')->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])->maxSize(10240),
                Forms\Components\Textarea::make('observations')->label('Observaciones')->rows(4)->columnSpanFull(),
            ])->columns(2)->collapsible(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Recepción')->schema([
                Infolists\Components\TextEntry::make('reference_code')->label('Referencia')->copyable(),
                Infolists\Components\TextEntry::make('status')->label('Estado')->formatStateUsing(fn ($state) => PackageReception::statuses()[$state] ?? $state)->badge()->color(fn ($state) => self::statusColor($state)),
                Infolists\Components\TextEntry::make('courier_name')->label('Correo'),
                Infolists\Components\TextEntry::make('owner.first_name')->label('Propietario')->formatStateUsing(fn ($record) => $record->owner->nombres()),
                Infolists\Components\TextEntry::make('lote.lote_id')->label('Lote')->formatStateUsing(fn ($record) => $record->lote->getNombre()),
                Infolists\Components\TextEntry::make('expected_from')->label('Ventana esperada')->formatStateUsing(fn ($record) => $record->expected_from->format('d/m/Y H:i').' – '.$record->expected_until->format('d/m/Y H:i')),
                Infolists\Components\TextEntry::make('tracking_number')->label('Seguimiento')->placeholder('Sin datos'),
                Infolists\Components\TextEntry::make('carrier_access_code')->label('Código o palabra clave')->placeholder('Sin código')->copyable()
                    ->visible(fn (PackageReception $record) => Auth::user()->can('viewSensitive', $record)),
                Infolists\Components\TextEntry::make('recipient_name')->label('Destinatario')->placeholder('Sin datos'),
                Infolists\Components\TextEntry::make('observations')->label('Observaciones')->placeholder('Sin observaciones')->columnSpanFull(),
            ])->columns(2),
            Infolists\Components\Section::make('Archivos privados')->schema([
                Infolists\Components\RepeatableEntry::make('files')->label('Adjuntos')->schema([
                    Infolists\Components\TextEntry::make('original_name')->label('Archivo')->placeholder('Adjunto')
                        ->url(fn (PackageReceptionFile $record) => route('package-receptions.files.download', $record))->openUrlInNewTab(),
                    Infolists\Components\TextEntry::make('category')->label('Tipo')->formatStateUsing(fn ($state) => [
                        PackageReceptionFile::REGISTRATION=>'Información previa',
                        PackageReceptionFile::RECEPTION=>'Foto de recepción',
                        PackageReceptionFile::DELIVERY_IDENTITY=>'Identidad de quien retiró',
                    ][$state] ?? $state)->badge(),
                    Infolists\Components\TextEntry::make('created_at')->label('Cargado')->dateTime('d/m/Y H:i'),
                ])->columns(3),
            ])->visible(fn (PackageReception $record) => $record->files()->exists()),
            Infolists\Components\Section::make('Trazabilidad')->schema([
                Infolists\Components\RepeatableEntry::make('events')->label('Historial')->schema([
                    Infolists\Components\TextEntry::make('event_type')->label('Evento')->formatStateUsing(fn ($state) => ['created'=>'Creado','received'=>'Recibido','delivered'=>'Entregado','cancelled'=>'Cancelado'][$state] ?? $state),
                    Infolists\Components\TextEntry::make('actor.name')->label('Realizado por')->placeholder('Sistema'),
                    Infolists\Components\TextEntry::make('occurred_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('notes')->label('Detalle')->placeholder('Sin nota'),
                ])->columns(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('reference_code')->label('Referencia')->searchable()->copyable()->weight('bold'),
            Tables\Columns\TextColumn::make('courier_name')->label('Correo')->searchable()->description(fn (PackageReception $r) => $r->tracking_number),
            Tables\Columns\TextColumn::make('owner.first_name')->label('Propietario')->formatStateUsing(fn (PackageReception $r) => $r->owner->nombres())->searchable(['first_name','last_name']),
            Tables\Columns\TextColumn::make('lote.lote_id')->label('Lote')->formatStateUsing(fn (PackageReception $r) => $r->lote->getNombre()),
            Tables\Columns\TextColumn::make('expected_from')->label('Llegada prevista')->formatStateUsing(fn (PackageReception $r) => $r->expected_from->format('d/m H:i').' – '.$r->expected_until->format('d/m H:i'))->sortable(),
            Tables\Columns\TextColumn::make('status')->label('Estado')->formatStateUsing(fn ($state) => PackageReception::statuses()[$state] ?? $state)->badge()->color(fn ($state) => self::statusColor($state))
                ->description(fn (PackageReception $r) => $r->isExpectedOverdue() ? 'No llegó en horario' : ($r->pickupAlertLevel() ? 'Pendiente de retiro' : null)),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->label('Estado')->options(PackageReception::statuses()),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make()->visible(fn (PackageReception $r) => Auth::user()->can('update', $r)),
            self::receiveTableAction(), self::deliverTableAction(), self::cancelTableAction(),
        ])->defaultSort('created_at', 'desc')->poll('30s');
    }

    public static function receiveTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('receive')->label('Recibir')->icon('heroicon-o-inbox-arrow-down')->color('success')
            ->visible(fn (PackageReception $r) => $r->status === PackageReception::EXPECTED && Auth::user()->can('receive', $r))->form([
                Forms\Components\TextInput::make('received_packages_count')->label('Cantidad de bultos')->numeric()->minValue(1),
                Forms\Components\FileUpload::make('reception_files')->label('Foto al recibir')->multiple()->image()->disk('local')->directory('package-receptions/reception')->visibility('private')->maxSize(10240),
                Forms\Components\Textarea::make('reception_notes')->label('Observación'),
            ])->action(function (PackageReception $record, array $data) {
                abort_unless(Auth::user()->can('receive', $record), 403);
                app(PackageReceptionService::class)->receive($record, Auth::user(), $data);
                Notification::make()->title('Paquete marcado como recibido')->success()->send();
            });
    }

    public static function deliverTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deliver')->label('Entregar')->icon('heroicon-o-hand-raised')->color('primary')
            ->visible(fn (PackageReception $r) => $r->status === PackageReception::RECEIVED && Auth::user()->can('deliver', $r))->form([
                Forms\Components\Radio::make('delivered_to_type')->label('¿Quién retira?')->options(['owner'=>'Propietario','third_party'=>'Otra persona'])->default('owner')->inline()->live()->required(),
                Forms\Components\TextInput::make('delivered_to_name')->label('Nombre completo')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party'),
                Forms\Components\TextInput::make('delivered_to_dni')->label('DNI')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party'),
                Forms\Components\FileUpload::make('delivery_identity_files')->label('Imagen del DNI')->multiple()->image()->disk('local')->directory('package-receptions/delivery-identity')->visibility('private')->visible(fn (Get $get) => $get('delivered_to_type') === 'third_party')->required(fn (Get $get) => $get('delivered_to_type') === 'third_party')->maxSize(10240),
                Forms\Components\Textarea::make('delivery_notes')->label('Observación'),
            ])->action(function (PackageReception $record, array $data) {
                abort_unless(Auth::user()->can('deliver', $record), 403);
                app(PackageReceptionService::class)->deliver($record, Auth::user(), $data);
                Notification::make()->title('Entrega registrada')->success()->send();
            });
    }

    public static function cancelTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('cancel')->label('Cancelar')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (PackageReception $r) => $r->status === PackageReception::EXPECTED && Auth::user()->can('cancel', $r))->requiresConfirmation()->form([
                Forms\Components\Textarea::make('cancellation_reason')->label('Motivo de cancelación')->required(),
            ])->action(function (PackageReception $record, array $data) {
                abort_unless(Auth::user()->can('cancel', $record), 403);
                app(PackageReceptionService::class)->cancel($record, Auth::user(), $data);
                Notification::make()->title('Recepción cancelada')->success()->send();
            });
    }

    public static function statusColor(string $status): string
    {
        return [PackageReception::EXPECTED=>'warning',PackageReception::RECEIVED=>'info',PackageReception::DELIVERED=>'success',PackageReception::CANCELLED=>'danger'][$status] ?? 'gray';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackageReceptions::route('/'),
            'create' => Pages\CreatePackageReception::route('/create'),
            'view' => Pages\ViewPackageReception::route('/{record}'),
            'edit' => Pages\EditPackageReception::route('/{record}/edit'),
        ];
    }
}
