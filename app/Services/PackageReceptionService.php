<?php

namespace App\Services;

use App\Filament\Resources\PackageReceptionResource;
use App\Models\PackageReception;
use App\Models\PackageReceptionFile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageReceptionService
{
    public function receive(PackageReception $reception, User $actor, array $data): PackageReception
    {
        $result = DB::transaction(function () use ($reception, $actor, $data) {
            $record = PackageReception::lockForUpdate()->findOrFail($reception->id);
            $this->assertStatus($record, PackageReception::EXPECTED);
            $record->update([
                'status' => PackageReception::RECEIVED,
                'received_at' => now(),
                'received_by_user_id' => $actor->id,
                'received_packages_count' => $data['received_packages_count'] ?? $record->expected_packages_count,
                'reception_notes' => $data['reception_notes'] ?? null,
            ]);
            $this->attachFiles($record, PackageReceptionFile::RECEPTION, $data['reception_files'] ?? [], $actor);
            $this->event($record, 'received', PackageReception::EXPECTED, PackageReception::RECEIVED, $actor, $data['reception_notes'] ?? null);
            return $record->fresh();
        });
        $this->notifyOwner($result, 'Paquete recibido', 'Recepción registró la llegada de '.$result->courier_name.'.');
        return $result;
    }

    public function cancel(PackageReception $reception, User $actor, array $data): PackageReception
    {
        $reason = trim((string) ($data['cancellation_reason'] ?? ''));
        if ($reason === '') throw ValidationException::withMessages(['cancellation_reason' => 'Debe indicar el motivo de cancelación.']);

        $result = DB::transaction(function () use ($reception, $actor, $reason) {
            $record = PackageReception::lockForUpdate()->findOrFail($reception->id);
            $this->assertStatus($record, PackageReception::EXPECTED);
            $record->update([
                'status' => PackageReception::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $actor->id,
                'cancellation_reason' => $reason,
            ]);
            $this->event($record, 'cancelled', PackageReception::EXPECTED, PackageReception::CANCELLED, $actor, $reason);
            return $record->fresh();
        });
        $this->notifyOwner($result, 'Recepción cancelada', 'Se canceló '.$result->reference_code.': '.$reason);
        return $result;
    }

    public function deliver(PackageReception $reception, User $actor, array $data): PackageReception
    {
        $type = $data['delivered_to_type'] ?? 'owner';
        if (! in_array($type, ['owner', 'third_party'], true)) {
            throw ValidationException::withMessages(['delivered_to_type' => 'Seleccione quién retira el paquete.']);
        }
        if ($type === 'third_party') {
            foreach (['delivered_to_name' => 'nombre', 'delivered_to_dni' => 'DNI'] as $field => $label) {
                if (blank($data[$field] ?? null)) throw ValidationException::withMessages([$field => "El {$label} es obligatorio."]);
            }
            if (empty($this->paths($data['delivery_identity_files'] ?? []))) {
                throw ValidationException::withMessages(['delivery_identity_files' => 'Debe adjuntar una imagen del DNI.']);
            }
        }

        $result = DB::transaction(function () use ($reception, $actor, $data, $type) {
            $record = PackageReception::lockForUpdate()->findOrFail($reception->id);
            $this->assertStatus($record, PackageReception::RECEIVED);
            $record->update([
                'status' => PackageReception::DELIVERED,
                'delivered_at' => now(),
                'delivered_by_user_id' => $actor->id,
                'delivered_to_type' => $type,
                'delivered_to_name' => $type === 'owner' ? $record->owner->nombres() : $data['delivered_to_name'],
                'delivered_to_dni' => $type === 'owner' ? (string) $record->owner->dni : $data['delivered_to_dni'],
                'delivery_notes' => $data['delivery_notes'] ?? null,
            ]);
            $this->attachFiles($record, PackageReceptionFile::DELIVERY_IDENTITY, $data['delivery_identity_files'] ?? [], $actor);
            $this->event($record, 'delivered', PackageReception::RECEIVED, PackageReception::DELIVERED, $actor, $data['delivery_notes'] ?? null, ['delivered_to_type' => $type]);
            return $record->fresh();
        });
        $this->notifyOwner($result, 'Paquete entregado', 'Se registró la entrega de '.$result->reference_code.'.');
        return $result;
    }

    public function recordCreation(PackageReception $record, User $actor): void
    {
        $this->event($record, 'created', null, PackageReception::EXPECTED, $actor, $record->observations);

        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['view_any_package::reception'],
            'Nueva solicitud de recepción de paquete',
            $actor->name.' registró '.$record->courier_name.' para el lote '.$record->lote?->getNombre().'.',
            ['type' => 'package_reception', 'package_reception_id' => $record->id, 'reference_code' => $record->reference_code, 'status' => PackageReception::EXPECTED],
            PackageReceptionResource::getUrl('view', ['record' => $record]),
            'heroicon-o-archive-box',
        );
    }

    private function assertStatus(PackageReception $record, string $expected): void
    {
        if ($record->status !== $expected) {
            throw ValidationException::withMessages(['status' => 'La recepción cambió de estado. Actualice la pantalla e intente nuevamente.']);
        }
    }

    private function attachFiles(PackageReception $record, string $category, mixed $files, User $actor): void
    {
        foreach ($this->paths($files) as $path) {
            $record->files()->create([
                'category' => $category,
                'path' => $path,
                'original_name' => basename($path),
                'uploaded_by_user_id' => $actor->id,
            ]);
        }
    }

    private function paths(mixed $files): array
    {
        if (is_string($files)) return [$files];
        if (! is_array($files)) return [];
        return collect(Arr::flatten($files))->filter(fn ($path) => is_string($path) && filled($path))->unique()->values()->all();
    }

    private function event(PackageReception $record, string $type, ?string $from, ?string $to, ?User $actor, ?string $notes = null, array $metadata = []): void
    {
        $record->events()->create([
            'event_type' => $type, 'from_status' => $from, 'to_status' => $to,
            'actor_user_id' => $actor?->id, 'notes' => $notes, 'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    public function notifyOwner(PackageReception $record, string $title, string $body): void
    {
        $user = $record->owner?->user;
        if (! $user) return;

        app(ApplicationNotificationService::class)->send(
            $user,
            $title,
            $body,
            [
                'type' => 'package_reception',
                'package_reception_id' => $record->id,
                'reference_code' => $record->reference_code,
                'status' => $record->status,
            ],
            PackageReceptionResource::getUrl('view', ['record' => $record]),
            'heroicon-o-archive-box',
        );
    }
}
