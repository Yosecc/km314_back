<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurrentVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\ApplicationNotificationService;

class RecurrentVisitorController extends Controller
{
    public function index(Request $request)
    {
        $owner = $request->user()->owner;

        return response()->json([
            'visitantes' => RecurrentVisitor::query()
                ->where('owner_id', $owner->id)
                ->with('autos')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request, true);
        $owner = $request->user()->owner;

        $visitor = DB::transaction(function () use ($request, $data, $owner) {
            $visitor = RecurrentVisitor::create([
                'owner_id' => $owner->id,
                'dni' => $data['dni'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'observations' => $data['observations'],
                'identity_document' => $request->file('identity_document')
                    ? $request->file('identity_document')->store('recurrent-visitors/identity-documents', 'public')
                    : null,
                'status' => 'pendiente',
                'user_id' => $request->user()->id,
            ]);

            $this->syncVehicles($visitor, $request, $data['vehicles']);

            return $visitor;
        });

        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['aprobar_recurrent::visitor', 'rechazar_recurrent::visitor'],
            'Nuevo visitante recurrente pendiente de aprobación',
            $request->user()->name.' registró a '.$visitor->nombres().'.',
            ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $visitor->id, 'status' => 'pendiente'],
            \App\Filament\Resources\RecurrentVisitorResource::getUrl('index'),
            'heroicon-o-user-plus',
        );

        return response()->json([
            'status' => true,
            'message' => 'Visitante recurrente registrado y enviado a aprobación.',
            'visitante' => $visitor->load('autos'),
        ], 201);
    }

    public function update(Request $request, RecurrentVisitor $recurrentVisitor)
    {
        $owner = $request->user()->owner;
        abort_unless($recurrentVisitor->owner_id === $owner->id, 404);

        $data = $this->validatedData($request, false);

        DB::transaction(function () use ($request, $data, $recurrentVisitor) {
            $attributes = [
                'dni' => $data['dni'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'observations' => $data['observations'],
                // Como en el recurso de Filament, una edición del propietario
                // vuelve a requerir la aprobación de administración.
                'status' => 'pendiente',
            ];

            if ($request->hasFile('identity_document')) {
                $attributes['identity_document'] = $request->file('identity_document')
                    ->store('recurrent-visitors/identity-documents', 'public');
            }

            $recurrentVisitor->update($attributes);
            $this->syncVehicles($recurrentVisitor, $request, $data['vehicles']);
        });

        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['aprobar_recurrent::visitor', 'rechazar_recurrent::visitor'],
            'Visitante recurrente actualizado para revisión',
            $request->user()->name.' actualizó a '.$recurrentVisitor->nombres().'.',
            ['type' => 'recurrent_visitor', 'recurrent_visitor_id' => $recurrentVisitor->id, 'status' => 'pendiente'],
            \App\Filament\Resources\RecurrentVisitorResource::getUrl('index'),
            'heroicon-o-pencil-square',
        );

        return response()->json([
            'status' => true,
            'message' => 'Visitante recurrente actualizado y enviado a aprobación.',
            'visitante' => $recurrentVisitor->fresh()->load('autos'),
        ]);
    }

    private function validatedData(Request $request, bool $identityDocumentRequired): array
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required', 'digits_between:7,8'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'identity_document' => [
                $identityDocumentRequired ? 'required' : 'nullable',
                'file',
                'max:10240',
            ],
            'vehicles' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $vehicles = $this->decodeVehicles($request->input('vehicles'));
        $vehicleValidator = Validator::make(['vehicles' => $vehicles], [
            'vehicles' => ['array'],
            'vehicles.*.id' => ['nullable', 'integer'],
            'vehicles.*.marca' => ['required', 'string', 'max:255'],
            'vehicles.*.modelo' => ['required', 'string', 'max:255'],
            'vehicles.*.patente' => ['required', 'string', 'max:255'],
            'vehicles.*.color' => ['nullable', 'string', 'max:255'],
        ]);

        if ($vehicleValidator->fails()) {
            throw new \Illuminate\Validation\ValidationException($vehicleValidator);
        }

        return [
            'dni' => $request->input('dni'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => filled($request->input('phone')) ? $request->input('phone') : null,
            'observations' => filled($request->input('observations')) ? $request->input('observations') : null,
            'vehicles' => $vehicles,
        ];
    }

    private function decodeVehicles(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        $vehicles = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($vehicles)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vehicles' => ['Los vehículos enviados no tienen un formato válido.'],
            ]);
        }

        return $vehicles;
    }

    private function syncVehicles(RecurrentVisitor $visitor, Request $request, array $vehicles): void
    {
        $existing = $visitor->autos()->get()->keyBy('id');
        $incomingIds = collect($vehicles)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach ($existing->except($incomingIds) as $vehicle) {
            $vehicle->delete();
        }

        foreach ($vehicles as $vehicle) {
            $record = !empty($vehicle['id']) ? $existing->get((int) $vehicle['id']) : null;
            $attributes = [
                'marca' => $vehicle['marca'],
                'modelo' => $vehicle['modelo'],
                'patente' => $vehicle['patente'],
                'color' => $vehicle['color'] ?? null,
            ];

            if ($record) {
                $record->update($attributes);
                continue;
            }

            $visitor->autos()->create($attributes + [
                'user_id' => $request->user()->id,
                'model' => 'RecurrentVisitor',
                'model_id' => $visitor->id,
            ]);
        }
    }
}
