<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\FilesRequired;
use App\Models\FormControl;
use App\Models\FormControlTypeIncome;
use App\Models\Lote;
use App\Models\Proveedor;
use App\Models\RecurrentVisitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\ApplicationNotificationService;

class MobileFormControlController extends Controller
{
    private const TEMPORARY_VISIT = 'Visita Temporal (24hs)';
    private const WORKER = 'Trabajador';
    private const RECURRENT_VISIT = 'Visita Recurrente';
    private const PROVIDER = 'Proveedor';

    public function configuration(Request $request)
    {
        $owner = $request->user()->owner;
        $workers = $this->ownerWorkers($owner->id);
        $recurrentVisitors = RecurrentVisitor::where('owner_id', $owner->id)
            ->with('autos.files')->orderBy('first_name')->get();

        return response()->json([
            'lotes' => Lote::where('owner_id', $owner->id)->with('sector')->get()
                ->map(fn (Lote $lote) => ['value' => $lote->sector?->name . $lote->lote_id]),
            'tipos_ingreso' => FormControlTypeIncome::where('status', true)->orderBy('id')->get()
                ->map(fn (FormControlTypeIncome $type) => [
                    'name' => $type->name,
                    'color' => $type->color,
                    'terminos' => $type->terminos,
                    'documentos_persona' => $this->documentsFor($type->name),
                ]),
            'documentos_vehiculo' => $this->documentsFor('car'),
            'trabajadores' => $workers->map(fn (Employee $worker) => [
                'id' => $worker->id,
                'dni' => (string) $worker->dni,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'phone' => $worker->phone,
                'autos' => $worker->autos->map(fn ($auto) => $this->autoData($auto)),
                ...$this->workerStatus($worker),
            ]),
            'visitantes_recurrentes' => $recurrentVisitors->map(fn (RecurrentVisitor $visitor) => [
                'id' => $visitor->id,
                'dni' => (string) $visitor->dni,
                'first_name' => $visitor->first_name,
                'last_name' => $visitor->last_name,
                'phone' => $visitor->phone,
                'autos' => $visitor->autos->map(fn ($auto) => $this->autoData($auto)),
                ...$this->recurrentVisitorStatus($visitor),
            ]),
            'proveedores' => Proveedor::where('status', true)->with('autos')->orderBy('nombre_empresa')->get()
                ->map(fn (Proveedor $provider) => [
                    'id' => $provider->id,
                    'name' => $provider->nombre_empresa,
                    'autos' => $provider->autos->map(fn ($auto) => $this->autoData($auto)),
                ]),
            'horario_trabajador' => ['inicio' => '07:00', 'fin' => '18:00', 'intervalo_minutos' => 30],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $owner = $request->user()->owner;
        $this->validateOwnerLote($owner->id, $data['lote']);

        $form = DB::transaction(function () use ($request, $data, $owner) {
            $ranges = $this->normalisedRanges($data['income_type'], $data['ranges']);
            $firstRange = $ranges[0];
            $form = FormControl::create([
                'owner_id' => $owner->id,
                'user_id' => $request->user()->id,
                'is_moroso' => false,
                'access_type' => ['lote'],
                'lote_ids' => [$data['lote']],
                'income_type' => [$data['income_type']],
                'proveedor_id' => $data['income_type'] === self::PROVIDER ? $data['provider_id'] : null,
                'start_date_range' => $firstRange['start_date'],
                'start_time_range' => $firstRange['start_time'],
                'end_date_range' => $firstRange['end_date'],
                'end_time_range' => $firstRange['end_time'],
                'date_unilimited' => false,
                'observations' => $data['observations'],
                'status' => $data['income_type'] === self::TEMPORARY_VISIT ? 'Authorized' : 'Pending',
            ]);

            $form->dateRanges()->createMany(array_map(fn ($range) => [
                'start_date_range' => $range['start_date'], 'start_time_range' => $range['start_time'],
                'end_date_range' => $range['end_date'], 'end_time_range' => $range['end_time'],
                'date_unilimited' => false,
            ], $ranges));

            $this->storePeople($form, $request, $data);
            $this->storeAutos($form, $request, $data);
            $this->storeExtraFiles($form, $request, $data['extra_files']);
            $form->mascotas()->createMany($data['pets']);

            return $form;
        });

        $isAutomatic = $form->status === 'Authorized';
        app(ApplicationNotificationService::class)->sendToPermissionHolders(
            ['aprobar_form::control', 'rechazar_form::control'],
            $isAutomatic ? 'Nuevo formulario autorizado automáticamente' : 'Nuevo formulario pendiente de aprobación',
            $isAutomatic
                ? 'El formulario #'.$form->id.' corresponde a una visita temporal de 24 horas.'
                : 'El formulario #'.$form->id.' espera la revisión de administración.',
            ['type' => 'form_control', 'form_control_id' => $form->id, 'status' => $form->status],
            \App\Filament\Resources\FormControlResource::getUrl('view', ['record' => $form]),
            'heroicon-o-document-check',
        );

        return response()->json([
            'status' => true,
            'message' => $form->status === 'Authorized'
                ? 'Formulario creado y autorizado por 24 horas.'
                : 'Formulario enviado para aprobación de administración.',
            'formulario' => $form->load(['dateRanges', 'peoples.files', 'autos.files', 'files', 'mascotas']),
        ], 201);
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'lote' => ['required', 'string', 'max:255'],
            'income_type' => ['required', 'string', 'exists:form_control_type_incomes,name'],
            'provider_id' => ['nullable', 'integer'],
            'ranges' => ['nullable', 'string'], 'people' => ['nullable', 'string'],
            'worker_ids' => ['nullable', 'string'], 'recurrent_visitor_ids' => ['nullable', 'string'],
            'autos' => ['nullable', 'string'], 'pets' => ['nullable', 'string'],
            'extra_files_meta' => ['nullable', 'string'], 'observations' => ['nullable', 'string', 'max:2000'],
            'accept_terms' => ['accepted'],
        ]);
        if ($validator->fails()) throw new ValidationException($validator);

        $data = [
            'lote' => $request->input('lote'), 'income_type' => $request->input('income_type'),
            'provider_id' => $request->input('provider_id'), 'ranges' => $this->decode($request->input('ranges'), 'ranges'),
            'people' => $this->decode($request->input('people'), 'people'),
            'worker_ids' => $this->decode($request->input('worker_ids'), 'worker_ids'),
            'recurrent_visitor_ids' => $this->decode($request->input('recurrent_visitor_ids'), 'recurrent_visitor_ids'),
            'autos' => $this->decode($request->input('autos'), 'autos'), 'pets' => $this->decode($request->input('pets'), 'pets'),
            'extra_files' => $this->decode($request->input('extra_files_meta'), 'extra_files_meta'),
            'observations' => filled($request->input('observations')) ? $request->input('observations') : null,
        ];

        $arrayValidator = Validator::make($data, [
            'ranges' => ['array', 'min:1'], 'ranges.*.start_date' => ['required', 'date_format:Y-m-d'],
            'ranges.*.start_time' => ['required', 'date_format:H:i'], 'ranges.*.end_date' => ['nullable', 'date_format:Y-m-d'],
            'ranges.*.end_time' => ['nullable', 'date_format:H:i'], 'people' => ['array'],
            'people.*.dni' => ['required', 'string', 'max:30'], 'people.*.first_name' => ['required', 'string', 'max:255'],
            'people.*.last_name' => ['required', 'string', 'max:255'], 'people.*.phone' => ['nullable', 'string', 'max:50'],
            'worker_ids' => ['array'], 'worker_ids.*' => ['integer'], 'recurrent_visitor_ids' => ['array'],
            'recurrent_visitor_ids.*' => ['integer'], 'autos' => ['array'], 'autos.*.marca' => ['required', 'string', 'max:255'],
            'autos.*.modelo' => ['required', 'string', 'max:255'], 'autos.*.patente' => ['required', 'string', 'max:255'],
            'autos.*.color' => ['nullable', 'string', 'max:255'], 'pets' => ['array'],
            'pets.*.tipo_mascota' => ['required', 'string', 'max:255'], 'pets.*.raza' => ['nullable', 'string', 'max:255'],
            'pets.*.nombre' => ['nullable', 'string', 'max:255'], 'pets.*.is_vacunado' => ['nullable', 'boolean'],
            'extra_files' => ['array'], 'extra_files.*.description' => ['nullable', 'string', 'max:255'],
        ]);
        if ($arrayValidator->fails()) throw new ValidationException($arrayValidator);

        $arrayValidator->after(function ($errors) use ($request, $data) {
            $this->validateRules($errors, $request, $data);
        });
        if ($arrayValidator->fails()) throw new ValidationException($arrayValidator);
        return $data;
    }

    private function validateRules($errors, Request $request, array $data): void
    {
        $today = now('America/Argentina/Buenos_Aires')->startOfDay();
        if ($data['income_type'] === self::PROVIDER && !Proveedor::whereKey($data['provider_id'])->where('status', true)->exists()) {
            $errors->add('provider_id', 'Seleccioná un proveedor activo.');
        }
        if ($data['income_type'] !== self::PROVIDER && empty($data['people']) && empty($data['worker_ids']) && empty($data['recurrent_visitor_ids'])) {
            $errors->add('people', 'Agregá al menos una persona.');
        }
        foreach ($data['ranges'] as $index => $range) {
            $start = Carbon::parse($range['start_date']);
            if ($start->lt($today)) $errors->add("ranges.$index.start_date", 'La fecha no puede ser anterior a hoy.');
            if ($data['income_type'] === self::WORKER) {
                if ($start->isSunday()) $errors->add("ranges.$index.start_date", 'Los domingos no están permitidos para trabajadores.');
                if (($range['end_date'] ?? null) !== $range['start_date']) $errors->add("ranges.$index.end_date", 'La salida del trabajador debe ser el mismo día.');
                if (($range['end_time'] ?? null) !== '18:00') $errors->add("ranges.$index.end_time", 'La salida de trabajadores es a las 18:00.');
                if (!$this->workerTime($range['start_time'])) $errors->add("ranges.$index.start_time", 'El horario de entrada debe ser entre 07:00 y 18:00 cada 30 minutos.');
            } elseif (!in_array($data['income_type'], [self::TEMPORARY_VISIT, self::PROVIDER], true)) {
                if (empty($range['end_date']) || empty($range['end_time'])) $errors->add("ranges.$index", 'Completá fecha y hora de salida.');
                elseif (Carbon::parse("{$range['end_date']} {$range['end_time']}")->lte(Carbon::parse("{$range['start_date']} {$range['start_time']}"))) $errors->add("ranges.$index.end_time", 'La salida debe ser posterior a la entrada.');
            }
        }
        $this->validateSelections($errors, $request, $data);
    }

    private function validateSelections($errors, Request $request, array $data): void
    {
        $ownerId = $request->user()->owner->id;
        if ($data['income_type'] === self::WORKER) {
            if (empty($data['worker_ids'])) $errors->add('worker_ids', 'Seleccioná al menos un trabajador.');
            $workers = $this->ownerWorkers($ownerId)->whereIn('id', $data['worker_ids']);
            if ($workers->count() !== count(array_unique($data['worker_ids']))) $errors->add('worker_ids', 'Uno de los trabajadores no pertenece a tu perfil.');
            foreach ($workers as $worker) if (!$this->workerStatus($worker)['available']) $errors->add('worker_ids', "{$worker->nombres()}: {$this->workerStatus($worker)['reason']}");
        }
        if ($data['income_type'] === self::RECURRENT_VISIT) {
            $visitors = RecurrentVisitor::where('owner_id', $ownerId)->with('autos.files')->whereIn('id', $data['recurrent_visitor_ids'])->get();
            if (empty($data['recurrent_visitor_ids'])) $errors->add('recurrent_visitor_ids', 'Seleccioná al menos un visitante recurrente.');
            if ($visitors->count() !== count(array_unique($data['recurrent_visitor_ids']))) $errors->add('recurrent_visitor_ids', 'Uno de los visitantes no pertenece a tu perfil.');
            foreach ($visitors as $visitor) if (!$this->recurrentVisitorStatus($visitor)['available']) $errors->add('recurrent_visitor_ids', "{$visitor->nombres()}: {$this->recurrentVisitorStatus($visitor)['reason']}");
        }
    }

    private function validateDocuments($errors, Request $request, array $data): void
    {
        $personDocs = $this->documentsFor($data['income_type']);
        foreach ($data['people'] as $index => $person) $this->validateDocumentSet($errors, $request, $person['documents'] ?? [], $personDocs, 'person_documents', "persona $index");
        foreach ($data['autos'] as $index => $auto) $this->validateDocumentSet($errors, $request, $auto['documents'] ?? [], $this->documentsFor('car'), 'auto_documents', "vehículo $index");
    }

    private function validateDocumentSet($errors, Request $request, array $documents, array $config, string $key, string $label): void
    {
        foreach ($config as $document) {
            $item = collect($documents)->firstWhere('name', $document['name']);
            $hasFile = $item && isset($item['file_index']) && $request->hasFile("$key.{$item['file_index']}");
            if ($document['required'] && !$hasFile) $errors->add($key, "Adjuntá {$document['name']} para $label.");
            if ($document['date_required'] && empty($item['expires_at'] ?? null)) $errors->add($key, "Indicá el vencimiento de {$document['name']} para $label.");
        }
    }

    private function storePeople(FormControl $form, Request $request, array $data): void
    {
        $people = $data['people'];
        if ($data['income_type'] === self::WORKER) {
            $people = $this->ownerWorkers($request->user()->owner->id)->whereIn('id', $data['worker_ids'])->map(fn ($worker) => [
                'dni' => $worker->dni, 'first_name' => $worker->first_name, 'last_name' => $worker->last_name, 'phone' => $worker->phone, 'source_autos' => $worker->autos,
            ])->all();
        }
        if ($data['income_type'] === self::RECURRENT_VISIT) {
            $people = RecurrentVisitor::where('owner_id', $request->user()->owner->id)->with('autos')->whereIn('id', $data['recurrent_visitor_ids'])->get()->map(fn ($visitor) => [
                'dni' => $visitor->dni, 'first_name' => $visitor->first_name, 'last_name' => $visitor->last_name, 'phone' => $visitor->phone, 'source_autos' => $visitor->autos,
            ])->all();
        }
        foreach ($people as $index => $person) {
            $record = $form->peoples()->create([
                'dni' => $person['dni'], 'first_name' => $person['first_name'], 'last_name' => $person['last_name'], 'phone' => $person['phone'] ?? null,
                'is_responsable' => (bool) ($person['is_responsable'] ?? false), 'is_acompanante' => (bool) ($person['is_acompanante'] ?? false), 'is_menor' => (bool) ($person['is_menor'] ?? false),
            ]);
            $this->storeDocumentSet($record->files(), $request, $person['documents'] ?? [], 'person_documents', 'form-controls/people');
            foreach (($person['source_autos'] ?? []) as $auto) $this->createAuto($form, $auto->toArray(), $request->user()->id);
        }
    }

    private function storeAutos(FormControl $form, Request $request, array $data): void
    {
        if ($data['income_type'] === self::PROVIDER) return;
        foreach ($data['autos'] as $auto) {
            $record = $this->createAuto($form, $auto, $request->user()->id);
            $this->storeDocumentSet($record->files(), $request, $auto['documents'] ?? [], 'auto_documents', 'form-controls/autos');
        }
    }

    private function createAuto(FormControl $form, array $auto, int $userId)
    {
        return $form->autos()->create([
            'marca' => $auto['marca'], 'modelo' => $auto['modelo'], 'patente' => $auto['patente'], 'color' => $auto['color'] ?? null,
            'user_id' => $userId, 'model' => 'FormControl', 'model_id' => $form->id,
        ]);
    }

    private function storeDocumentSet($relation, Request $request, array $documents, string $key, string $directory): void
    {
        foreach ($documents as $document) {
            if (!isset($document['file_index'])) continue;
            $file = $request->file("$key.{$document['file_index']}");
            if (!$file) continue;
            $relation->create(['name' => $document['name'] ?? 'Documento', 'file' => $file->store($directory, 'public'), 'fecha_vencimiento' => $document['expires_at'] ?? null]);
        }
    }

    private function storeExtraFiles(FormControl $form, Request $request, array $files): void
    {
        foreach ($files as $file) {
            if (!isset($file['file_index']) || !($upload = $request->file("extra_files.{$file['file_index']}"))) continue;
            $form->files()->create(['user_id' => $request->user()->id, 'file' => $upload->store('form-controls/extra', 'public'), 'description' => $file['description'] ?? null]);
        }
    }

    private function normalisedRanges(string $type, array $ranges): array
    {
        if ($type === self::TEMPORARY_VISIT) {
            $now = now('America/Argentina/Buenos_Aires');
            return [['start_date' => $now->format('Y-m-d'), 'start_time' => $now->format('H:i'), 'end_date' => $now->copy()->addDay()->format('Y-m-d'), 'end_time' => $now->format('H:i')]];
        }
        return array_map(function ($range) use ($type) {
            if ($type === self::WORKER) { $range['end_date'] = $range['start_date']; $range['end_time'] = '18:00'; }
            if ($type === self::PROVIDER) { $range['start_time'] = '07:00'; $range['end_date'] = $range['start_date']; $range['end_time'] = '18:00'; }
            return $range;
        }, $ranges);
    }

    private function ownerWorkers(int $ownerId)
    {
        return Employee::where(fn ($query) => $query->whereHas('owners', fn ($owners) => $owners->where('owner_id', $ownerId))->orWhere('owner_id', $ownerId))
            ->with(['files', 'autos.files'])->orderBy('first_name')->get();
    }

    private function workerStatus(Employee $worker): array
    {
        if ($worker->status !== 'aprobado') return ['available' => false, 'reason' => 'Debe ser aprobado por administración.'];
        if ($worker->vencidosFile()) return ['available' => false, 'reason' => 'Tiene documentos personales vencidos.'];
        if ($worker->vencidosAutosFile()) return ['available' => false, 'reason' => 'Tiene documentos de vehículo vencidos.'];
        if ($worker->isReverificacion()) return ['available' => false, 'reason' => 'Requiere reverificación.'];
        return ['available' => true, 'reason' => 'Aprobado y documentación al día.'];
    }

    private function recurrentVisitorStatus(RecurrentVisitor $visitor): array
    {
        if ($visitor->status !== 'aprobado') return ['available' => false, 'reason' => 'Debe ser aprobado por administración.'];
        if ($visitor->vencidosAutosFile()) return ['available' => false, 'reason' => 'Tiene documentos de vehículo vencidos.'];
        return ['available' => true, 'reason' => 'Aprobado y documentación al día.'];
    }

    private function documentsFor(string $type): array
    {
        return collect(FilesRequired::where('type', $type)->first()?->required ?? [])->map(fn ($item) => [
            'name' => $item['document'] ?? $item['name'] ?? 'Documento', 'required' => in_array($item['is_required'] ?? false, [true, 1, '1'], true),
            'date_required' => in_array($item['date_is_required'] ?? false, [true, 1, '1'], true),
        ])->values()->all();
    }

    private function decode(?string $value, string $name): array
    {
        if (blank($value)) return [];
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) throw ValidationException::withMessages([$name => ['El formato enviado no es válido.']]);
        return $decoded;
    }

    private function workerTime(string $time): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        return $hour >= 7 && $hour <= 18 && $minute % 30 === 0 && !($hour === 18 && $minute > 0);
    }

    private function validateOwnerLote(int $ownerId, string $lote): void
    {
        $exists = Lote::where('owner_id', $ownerId)->with('sector')->get()->contains(fn ($item) => ($item->sector?->name . $item->lote_id) === $lote);
        abort_unless($exists, 422, 'El lote seleccionado no pertenece al propietario.');
    }

    private function autoData($auto): array
    {
        return ['marca' => $auto->marca, 'modelo' => $auto->modelo, 'patente' => $auto->patente, 'color' => $auto->color];
    }
}
