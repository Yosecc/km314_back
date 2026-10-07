<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\PackageReception;
use App\Models\PackageReceptionFile;
use App\Services\PackageReceptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MobilePackageReceptionController extends Controller
{
    public function index(Request $request)
    {
        $owner = $this->owner($request);

        return response()->json([
            'recepciones' => PackageReception::query()
                ->where('owner_id', $owner->id)
                ->with(['lote.sector', 'files', 'events.actor'])
                ->latest()
                ->get()
                ->map(fn (PackageReception $reception) => $this->payload($reception)),
        ]);
    }

    public function configuration(Request $request)
    {
        $owner = $this->owner($request);

        return response()->json([
            'lotes' => Lote::query()->where('owner_id', $owner->id)->with('sector')->get()
                ->map(fn (Lote $lote) => [
                    'id' => $lote->id,
                    'nombre' => $lote->getNombre(),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $owner = $this->owner($request);
        $data = $this->validatedData($request, $owner);

        $reception = DB::transaction(function () use ($request, $owner, $data) {
            $record = PackageReception::create([
                'owner_id' => $owner->id,
                'lote_id' => $data['lote_id'],
                'created_by_user_id' => $request->user()->id,
                ...$this->attributes($data),
            ]);
            $this->storeRegistrationFiles($record, $request);
            app(PackageReceptionService::class)->recordCreation($record, $request->user());

            return $record;
        });

        return response()->json([
            'status' => true,
            'message' => 'La recepción fue agendada. Podrás ver aquí cuando llegue tu paquete.',
            'recepcion' => $this->payload($reception->fresh(['lote.sector', 'files', 'events.actor'])),
        ], 201);
    }

    public function update(Request $request, PackageReception $packageReception)
    {
        $owner = $this->owner($request);
        $this->belongsToOwner($packageReception, $owner);
        $this->assertExpected($packageReception);
        $data = $this->validatedData($request, $owner);

        DB::transaction(function () use ($request, $packageReception, $data) {
            $packageReception->update([
                'lote_id' => $data['lote_id'],
                ...$this->attributes($data),
            ]);
            $this->storeRegistrationFiles($packageReception, $request);
        });

        return response()->json([
            'status' => true,
            'message' => 'La recepción fue actualizada.',
            'recepcion' => $this->payload($packageReception->fresh(['lote.sector', 'files', 'events.actor'])),
        ]);
    }

    public function cancel(Request $request, PackageReception $packageReception)
    {
        $owner = $this->owner($request);
        $this->belongsToOwner($packageReception, $owner);
        $this->assertExpected($packageReception);

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        app(PackageReceptionService::class)->cancel($packageReception, $request->user(), $validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'La recepción fue cancelada.',
            'recepcion' => $this->payload($packageReception->fresh(['lote.sector', 'files', 'events.actor'])),
        ]);
    }

    private function owner(Request $request): Owner
    {
        $owner = $request->user()?->owner;
        abort_unless($owner instanceof Owner, 403, 'Esta sección está disponible para propietarios.');

        return $owner;
    }

    private function belongsToOwner(PackageReception $reception, Owner $owner): void
    {
        abort_unless($reception->owner_id === $owner->id, 404);
    }

    private function assertExpected(PackageReception $reception): void
    {
        if ($reception->status !== PackageReception::EXPECTED) {
            throw ValidationException::withMessages([
                'status' => ['Solo podés modificar o cancelar una recepción que aún está en espera.'],
            ]);
        }
    }

    private function validatedData(Request $request, Owner $owner): array
    {
        $validator = Validator::make($request->all(), [
            'lote_id' => ['required', 'integer'],
            'courier_name' => ['required', 'string', 'max:255'],
            'expected_date' => ['required', 'date_format:Y-m-d'],
            'expected_from_time' => ['required', 'date_format:H:i'],
            'expected_until_time' => ['required', 'date_format:H:i'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'carrier_access_code' => ['nullable', 'string', 'max:1000'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_dni' => ['nullable', 'string', 'max:50'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'expected_packages_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'registration_uploads' => ['nullable', 'array', 'max:10'],
            'registration_uploads.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $validator->after(function ($errors) use ($owner, $request) {
            if (! Lote::query()->where('owner_id', $owner->id)->whereKey($request->integer('lote_id'))->exists()) {
                $errors->add('lote_id', 'El lote seleccionado no pertenece a tu perfil.');
            }

            if (! $errors->has('expected_date') && ! $errors->has('expected_from_time') && ! $errors->has('expected_until_time')) {
                $from = Carbon::createFromFormat('Y-m-d H:i', "{$request->input('expected_date')} {$request->input('expected_from_time')}", 'America/Argentina/Buenos_Aires');
                $until = Carbon::createFromFormat('Y-m-d H:i', "{$request->input('expected_date')} {$request->input('expected_until_time')}", 'America/Argentina/Buenos_Aires');
                if ($until->lte($from)) {
                    $errors->add('expected_until_time', 'La hora hasta debe ser posterior a la hora desde.');
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function attributes(array $data): array
    {
        return [
            'courier_name' => trim($data['courier_name']),
            'expected_from' => Carbon::createFromFormat('Y-m-d H:i', "{$data['expected_date']} {$data['expected_from_time']}", 'America/Argentina/Buenos_Aires'),
            'expected_until' => Carbon::createFromFormat('Y-m-d H:i', "{$data['expected_date']} {$data['expected_until_time']}", 'America/Argentina/Buenos_Aires'),
            'tracking_number' => $this->nullable($data['tracking_number'] ?? null),
            'carrier_access_code' => $this->nullable($data['carrier_access_code'] ?? null),
            'recipient_name' => $this->nullable($data['recipient_name'] ?? null),
            'recipient_dni' => $this->nullable($data['recipient_dni'] ?? null),
            'recipient_phone' => $this->nullable($data['recipient_phone'] ?? null),
            'expected_packages_count' => $data['expected_packages_count'] ?? null,
            'observations' => $this->nullable($data['observations'] ?? null),
        ];
    }

    private function storeRegistrationFiles(PackageReception $reception, Request $request): void
    {
        foreach ((array) $request->file('registration_uploads', []) as $upload) {
            $reception->files()->create([
                'category' => PackageReceptionFile::REGISTRATION,
                'path' => $upload->store('package-receptions/registration', 'local'),
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType(),
                'size' => $upload->getSize(),
                'uploaded_by_user_id' => $request->user()->id,
            ]);
        }
    }

    private function payload(PackageReception $reception): array
    {
        return [
            'id' => $reception->id,
            'reference_code' => $reception->reference_code,
            'courier_name' => $reception->courier_name,
            'tracking_number' => $reception->tracking_number,
            'carrier_access_code' => $reception->carrier_access_code,
            'lote_id' => $reception->lote_id,
            'lote' => $reception->lote?->getNombre(),
            'expected_date' => $reception->expected_from?->format('Y-m-d'),
            'expected_from_time' => $reception->expected_from?->format('H:i'),
            'expected_until_time' => $reception->expected_until?->format('H:i'),
            'status' => $reception->status,
            'status_label' => PackageReception::statuses()[$reception->status] ?? $reception->status,
            'recipient_name' => $reception->recipient_name,
            'recipient_dni' => $reception->recipient_dni,
            'recipient_phone' => $reception->recipient_phone,
            'expected_packages_count' => $reception->expected_packages_count,
            'received_packages_count' => $reception->received_packages_count,
            'observations' => $reception->observations,
            'reception_notes' => $reception->reception_notes,
            'cancellation_reason' => $reception->cancellation_reason,
            'received_at' => $reception->received_at?->toIso8601String(),
            'delivered_at' => $reception->delivered_at?->toIso8601String(),
            'cancelled_at' => $reception->cancelled_at?->toIso8601String(),
            'is_overdue' => $reception->isExpectedOverdue(),
            'pickup_alert_level' => $reception->pickupAlertLevel(),
            'registration_files_count' => $reception->files->where('category', PackageReceptionFile::REGISTRATION)->count(),
            'events' => $reception->events->map(fn ($event) => [
                'type' => $event->event_type,
                'notes' => $event->notes,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ])->values(),
        ];
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
