<?php

namespace App\Filament\Resources\RecurrentVisitorResource\Traits;

use App\Models\RecurrentVisitor;
use App\Services\ApplicationNotificationService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Auth;

trait HasVehicleVerificationActions
{
    public static function requestVehicleVerificationAction(): TableAction
    {
        return TableAction::make('solicitar_verificacion_documentos')
            ->label('Solicitar verificación')
            ->icon('heroicon-o-shield-exclamation')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Solicitar verificación de vehículos')
            ->modalDescription('El visitante quedará pendiente hasta que la documentación de sus vehículos sea revisada.')
            ->visible(fn (RecurrentVisitor $record) => $record->status === 'aprobado'
                && (bool) $record->vencidosAutosFile()
                && Auth::user()->can('update', $record))
            ->action(function (RecurrentVisitor $record): void {
                abort_unless(Auth::user()->can('update', $record), 403);

                $record->update(['status' => 'pendiente']);

                Notification::make()
                    ->title('Verificación solicitada')
                    ->body('El visitante quedó pendiente de revisión documental.')
                    ->success()
                    ->send();

                if (Auth::user()->hasRole('owner')) {
                    app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                        ['aprobar_recurrent::visitor', 'rechazar_recurrent::visitor'],
                        'Solicitud de verificación de visitante recurrente',
                        Auth::user()->name.' solicitó revisar la documentación vehicular de '.$record->nombres().'.',
                        ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $record->id, 'status' => 'pendiente', 'verification_requested' => true],
                        self::getUrl('index'),
                        'heroicon-o-shield-exclamation',
                    );

                    return;
                }

                app(ApplicationNotificationService::class)->send(
                    self::ownerRecipients($record),
                    'Administración solicitó una verificación',
                    'Revisá y renová la documentación de los vehículos de '.$record->nombres().'.',
                    ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $record->id, 'status' => 'pendiente', 'verification_requested' => true],
                    self::getUrl('index'),
                    'heroicon-o-shield-exclamation',
                );
            });
    }

    public static function renewVehicleDocumentsAction(): TableAction
    {
        return TableAction::make('renovar_documentos_vehiculos')
            ->label('Renovar documentos')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->fillForm(function (RecurrentVisitor $record): array {
                return [
                    'vehicle_files' => $record->autos()->with('files')->get()
                        ->flatMap(fn ($auto) => $auto->files
                            ->filter(fn ($file) => $file->fecha_vencimiento && Carbon::parse($file->fecha_vencimiento)->isPast())
                            ->map(fn ($file) => [
                                'id' => $file->id,
                                'name' => $file->name,
                                'vehicle' => trim($auto->marca.' '.$auto->modelo.' · '.$auto->patente),
                                'fecha_vencimiento' => $file->fecha_vencimiento,
                                'file' => [$file->file],
                            ]))
                        ->values()
                        ->all(),
                ];
            })
            ->form([
                Placeholder::make('vehicle_documents_help')
                    ->content('Reemplazá únicamente los documentos vencidos de los vehículos y cargá una nueva fecha de vencimiento.')
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('vehicle_files')
                    ->label('Documentos de vehículos vencidos')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => ($state['vehicle'] ?? 'Vehículo').' · '.($state['name'] ?? 'Documento'))
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Hidden::make('name'),
                        Forms\Components\Hidden::make('vehicle'),
                        Forms\Components\DatePicker::make('fecha_vencimiento')
                            ->label('Nueva fecha de vencimiento')
                            ->minDate(now()->startOfDay())
                            ->required(),
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo actualizado')
                            ->helperText('Eliminá el archivo actual y subí el documento renovado.')
                            ->required()
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(fn ($file, $record) => $file ? $file->getClientOriginalName() : $record?->file),
                    ])
                    ->columns(2),
            ])
            ->visible(fn (RecurrentVisitor $record) => (bool) $record->vencidosAutosFile() && Auth::user()->can('update', $record))
            ->action(function (array $data, RecurrentVisitor $record): void {
                abort_unless(Auth::user()->can('update', $record), 403);

                $files = $record->autos()->with('files')->get()->flatMap->files->keyBy('id');
                $updated = 0;
                $invalid = 0;

                foreach ($data['vehicle_files'] ?? [] as $fileData) {
                    $file = $files->get($fileData['id'] ?? null);

                    if (! $file) {
                        continue;
                    }

                    if (Carbon::parse($fileData['fecha_vencimiento'])->isBefore(now()->startOfDay())) {
                        $invalid++;
                        continue;
                    }

                    $uploadedPath = $fileData['file'] ?? $file->file;
                    $file->update([
                        'fecha_vencimiento' => $fileData['fecha_vencimiento'],
                        'file' => is_array($uploadedPath) ? reset($uploadedPath) : $uploadedPath,
                    ]);
                    $updated++;
                }

                if ($updated === 0) {
                    Notification::make()->title('No se renovó ningún documento')->danger()->send();
                    return;
                }

                $record->update(['status' => 'pendiente']);

                Notification::make()
                    ->title($invalid ? 'Documentación renovada parcialmente' : 'Documentación renovada')
                    ->body($invalid ? "Se renovaron {$updated} documento(s); algunos conservan una fecha inválida." : "Se renovaron {$updated} documento(s) y quedaron pendientes de revisión.")
                    ->success()
                    ->send();

                if (Auth::user()->hasRole('owner')) {
                    app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
                        ['aprobar_recurrent::visitor', 'rechazar_recurrent::visitor'],
                        'Documentación vehicular renovada',
                        Auth::user()->name.' renovó documentos de los vehículos de '.$record->nombres().'. Requiere revisión.',
                        ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $record->id, 'status' => 'pendiente', 'documents_renewed' => true],
                        self::getUrl('index'),
                        'heroicon-o-document-arrow-up',
                    );
                }
            });
    }
}
