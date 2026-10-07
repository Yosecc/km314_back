<?php

namespace Database\Seeders;

use App\Models\Lote;
use App\Models\PackageReception;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageReceptionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $lotes = Lote::query()
            ->whereNotNull('owner_id')
            ->whereHas('owner', fn ($query) => $query->whereNull('deleted_at'))
            ->with(['owner.user'])
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($lotes->isEmpty()) {
            $this->command?->warn('No hay lotes con propietario. No se crearon recepciones de demostración.');
            return;
        }

        $fallbackUser = User::query()->firstOrFail();
        $scenarios = [
            [
                'reference_code' => 'DEMO-PAQ-ESPERADO',
                'courier_name' => 'Mercado Libre',
                'tracking_number' => 'ML-DEMO-10482',
                'expected_from' => now()->addHour(),
                'expected_until' => now()->addHours(4),
                'carrier_access_code' => 'CASA VERDE',
                'recipient_name' => 'Compra del propietario',
                'expected_packages_count' => 2,
                'status' => PackageReception::EXPECTED,
                'observations' => 'Entrega prevista para hoy. Escenario de demostración.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-NO-LLEGO',
                'courier_name' => 'Correo Argentino',
                'tracking_number' => 'CA-DEMO-7781',
                'expected_from' => now()->subHours(5),
                'expected_until' => now()->subHours(2),
                'status' => PackageReception::EXPECTED,
                'observations' => 'El horario terminó y el paquete todavía no llegó.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-RECIBIDO',
                'courier_name' => 'Andreani',
                'tracking_number' => 'AND-DEMO-2204',
                'expected_from' => now()->subHours(3),
                'expected_until' => now()->subHour(),
                'status' => PackageReception::RECEIVED,
                'received_at' => now()->subMinutes(35),
                'received_packages_count' => 1,
                'reception_notes' => 'Caja recibida en buen estado.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-DEMORADO',
                'courier_name' => 'OCA',
                'tracking_number' => 'OCA-DEMO-9015',
                'expected_from' => now()->subHours(32),
                'expected_until' => now()->subHours(30),
                'status' => PackageReception::RECEIVED,
                'received_at' => now()->subHours(28),
                'received_packages_count' => 3,
                'reception_notes' => 'Pendiente de retiro por más de 24 horas.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-CRITICO',
                'courier_name' => 'Urbano',
                'tracking_number' => 'URB-DEMO-3308',
                'expected_from' => now()->subDays(5),
                'expected_until' => now()->subDays(5)->addHours(3),
                'status' => PackageReception::RECEIVED,
                'received_at' => now()->subHours(96),
                'received_packages_count' => 1,
                'reception_notes' => 'Paquete olvidado en recepción. Alerta crítica.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-ENTREGADO',
                'courier_name' => 'DHL',
                'tracking_number' => 'DHL-DEMO-4812',
                'expected_from' => now()->subDays(2),
                'expected_until' => now()->subDays(2)->addHours(4),
                'status' => PackageReception::DELIVERED,
                'received_at' => now()->subDays(2)->addHours(2),
                'received_packages_count' => 1,
                'delivered_at' => now()->subDay(),
                'delivered_to_type' => 'owner',
                'delivery_notes' => 'Entregado al propietario sin novedades.',
            ],
            [
                'reference_code' => 'DEMO-PAQ-CANCELADO',
                'courier_name' => 'PedidosYa Envíos',
                'tracking_number' => 'PY-DEMO-5520',
                'expected_from' => now()->addHours(3),
                'expected_until' => now()->addHours(6),
                'status' => PackageReception::CANCELLED,
                'cancelled_at' => now()->subMinutes(20),
                'cancellation_reason' => 'La compra fue cancelada por el propietario.',
            ],
        ];

        DB::transaction(function () use ($scenarios, $lotes, $fallbackUser): void {
            foreach ($scenarios as $index => $scenario) {
                $lote = $lotes[$index % $lotes->count()];
                $actor = $lote->owner->user ?: $fallbackUser;
                $reference = $scenario['reference_code'];
                $record = PackageReception::withTrashed()->where('reference_code', $reference)->first();
                $data = array_merge([
                    'owner_id' => $lote->owner_id,
                    'lote_id' => $lote->id,
                    'created_by_user_id' => $actor->id,
                    'received_by_user_id' => isset($scenario['received_at']) ? $fallbackUser->id : null,
                    'cancelled_by_user_id' => isset($scenario['cancelled_at']) ? $actor->id : null,
                    'delivered_by_user_id' => isset($scenario['delivered_at']) ? $fallbackUser->id : null,
                ], $scenario);

                if ($record) {
                    $record->restore();
                    $record->update($data);
                } else {
                    $record = PackageReception::create($data);
                }

                $record->events()->delete();
                $record->events()->create([
                    'event_type' => 'created',
                    'to_status' => PackageReception::EXPECTED,
                    'actor_user_id' => $actor->id,
                    'notes' => 'Registro generado para demostración del monitor.',
                    'occurred_at' => $record->created_at,
                ]);

                if (in_array($record->status, [PackageReception::RECEIVED, PackageReception::DELIVERED], true)) {
                    $record->events()->create([
                        'event_type' => 'received',
                        'from_status' => PackageReception::EXPECTED,
                        'to_status' => PackageReception::RECEIVED,
                        'actor_user_id' => $fallbackUser->id,
                        'notes' => $record->reception_notes,
                        'occurred_at' => $record->received_at,
                    ]);
                }

                if ($record->status === PackageReception::DELIVERED) {
                    $record->events()->create([
                        'event_type' => 'delivered', 'from_status' => PackageReception::RECEIVED,
                        'to_status' => PackageReception::DELIVERED, 'actor_user_id' => $fallbackUser->id,
                        'notes' => $record->delivery_notes, 'occurred_at' => $record->delivered_at,
                    ]);
                }

                if ($record->status === PackageReception::CANCELLED) {
                    $record->events()->create([
                        'event_type' => 'cancelled', 'from_status' => PackageReception::EXPECTED,
                        'to_status' => PackageReception::CANCELLED, 'actor_user_id' => $actor->id,
                        'notes' => $record->cancellation_reason, 'occurred_at' => $record->cancelled_at,
                    ]);
                }
            }
        });

        $this->command?->info(count($scenarios).' recepciones de demostración creadas o actualizadas.');
    }
}
