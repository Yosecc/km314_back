<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SocialMessages;
use App\Mail\Contact;
use App\Mail\sendMailLanding;
use App\Models\Employee;
use App\Models\FilesRequired;
use App\Models\FormControlTypeIncome;
use App\Models\Landing;
use App\Models\LandingData;
use App\Models\Newsletter;
use App\Models\Owner;
use App\Models\OwnerSpontaneousVisit;
use App\Models\PopUp;
use App\Models\Slider;
use App\Models\Trabajos;
use App\Models\Works;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\ApplicationNotificationService;


class Main extends Controller
{
    public function sliders()
    {
        return Slider::where('status',1)->get()->map(function($slide){
            $slide['img'] = request()->getSchemeAndHttpHost().Storage::url($slide['img']);
            return $slide;
        })->pluck('img');
    }

    public function trabajos(Request $request)
    {
        return response()->json(Trabajos::get()->pluck('name','name')->toArray() ,200);
    }

    public function tipos_ingresos()
    {
        $data = FormControlTypeIncome::where('status',1)->with(['subtipos'])->get();

        return response()->json($data, 200);
    }

    public function empleados(Request $request)
    {
        $empleados = Employee::where('owner_id', $request->user()->owner->id)
                                ->orderBy('created_at','desc')
                                ->with(['autos.files','files','horarios'])
                                ->get();

        $empleados = $empleados->map(function($empleado){
            $empleado['phone'] == null ? $empleado['phone'] = 0 : $empleado['phone'];
            return $empleado;
        });

        return response()->json(['empleados'=>$empleados,'tipo_empleos' => Works::where('status', true)->orderBy('name','asc')->get()], 200);
    }

       
    public function empleadosStore(Request $request)
    {
        // Debug: Ver todos los datos que llegan
        \Log::info('empleadosStore - Request data:', [
            'all_data' => $request->all(),
            'files_data' => $request->file('files'),
            'has_files' => $request->hasFile('files'),
            'files_input' => $request->input('files'),
        ]);
    
        $validator = Validator::make($request->all(), [
            // 'work_id'  => 'required',
            'dni' => 'required',
            'first_name' => 'required',
            'last_name'  => 'required',
            'phone' => 'required',
            'fecha_vencimiento_seguro' => 'required',
            'files' => 'array', // Validar que files sea un array
            'files.*.name' => 'required|string', // Cada archivo debe tener nombre
            'files.*.file' => 'required|file', // Cada archivo debe ser un archivo válido
            'files.*.fecha_vencimiento' => 'nullable|date', // Fecha de vencimiento opcional
        ]);
    
        if ($validator->fails()) {
            \Log::error('empleadosStore - Validation failed:', $validator->errors()->toArray());
            return response()->json( ['status' => false, 'errors' => $validator->errors() ], 422);
        }
    
        // Usar create() en lugar de insert() para obtener el modelo creado
        $employee = Employee::create([
            'work_id' => 36,
            'dni' => $request->dni,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'user_id' => $request->user()->id,
            'fecha_vencimiento_seguro' => $request->fecha_vencimiento_seguro,
            'owner_id' => $request->user()->owner->id,
            'model_origen' => 'Owner',
            'model_origen_id' => $request->user()->owner->id,
            'status' => 'pendiente', // Agregar status por defecto
        ]);
    
        // Asociar la relación owners (tabla pivot)
        $employee->owners()->attach($request->user()->owner->id);
    
        \Log::info('empleadosStore - Employee created successfully', ['employee_id' => $employee->id]);
    
        // Debug: Verificar archivos antes de procesarlos
        \Log::info('empleadosStore - Files check:', [
            'has_files_input' => $request->has('files'),
            'files_is_array' => is_array($request->input('files')),
            'files_count' => $request->has('files') ? count($request->input('files', [])) : 0,
            'hasFile_files' => $request->hasFile('files'),
        ]);
    
        // Procesar y guardar archivos si existen - USAR LA MISMA LÓGICA QUE UPDATE
        if ($request->has('files') && is_array($request->input('files'))) {
            \Log::info('empleadosStore - Processing files...');
            
            $files = $request->input('files');
            foreach ($files as $index => $fileData) {
                \Log::info("empleadosStore - Processing file {$index}:", [
                    'file_data' => $fileData,
                    'has_file_key' => isset($fileData['file']),
                    'file_type' => isset($fileData['file']) ? gettype($fileData['file']) : 'not_set',
                ]);
    
                // Verificar si el archivo está en el request
                if ($request->hasFile("files.{$index}.file")) {
                    $file = $request->file("files.{$index}.file");
                    \Log::info("empleadosStore - File found:", [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
    
                    try {
                        // Guardar el archivo en storage
                        $filePath = $file->store('employee-files', 'public');
                        \Log::info("empleadosStore - File stored:", ['path' => $filePath]);
                        
                        // Crear el registro en la base de datos
                        $fileRecord = $employee->files()->create([
                            'name' => $fileData['name'],
                            'file' => $filePath,
                            'fecha_vencimiento' => $fileData['fecha_vencimiento'] ?? null,
                        ]);
                        
                        \Log::info("empleadosStore - File record created:", ['record_id' => $fileRecord->id]);
                        
                    } catch (\Exception $e) {
                        \Log::error("empleadosStore - Error processing file {$index}:", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    \Log::warning("empleadosStore - File not found in request:", ['index' => $index]);
                }
            }
        } else {
            \Log::info('empleadosStore - No files to process');
        }
    
        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['update_employee'],
            'Nuevo trabajador pendiente de revisión',
            $employee->nombres().' fue registrado y espera la aprobación de administración.',
            ['type' => 'employee', 'employee_id' => $employee->id, 'status' => 'pendiente'],
            \App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee]),
            'heroicon-o-user-plus',
        );

        return response()->json(['status' => true, 'message' => 'Registro guardado con archivos' ], 200);
    }

    
    public function empleadosUpdate(Request $request, $id)
    {
        // Debug: Ver todos los datos que llegan
        \Log::info('empleadosUpdate - Request data:', [
            'all_data' => $request->all(),
            'files_data' => $request->file('files'),
            'has_files' => $request->hasFile('files'),
            'files_input' => $request->input('files'),
        ]);
    
        $validator = Validator::make($request->all(), [
            'dni' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'fecha_vencimiento_seguro' => 'nullable|date',
            'files' => 'array', // Validar que files sea un array
            'files.*.name' => 'required|string', // Cada archivo debe tener nombre
            'files.*.file' => 'required|file', // Cada archivo debe ser un archivo válido
            'files.*.fecha_vencimiento' => 'nullable|date', // Fecha de vencimiento opcional
        ]);
    
        if ($validator->fails()) {
            \Log::error('empleadosUpdate - Validation failed:', $validator->errors()->toArray());
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
    
        // Buscar el empleado que pertenece al owner
        $employee = Employee::where('id', $id)
            ->where('owner_id', $request->user()->owner->id)
            ->first();
    
        if (!$employee) {
            \Log::error('empleadosUpdate - Employee not found:', ['id' => $id, 'owner_id' => $request->user()->owner->id]);
            return response()->json(['status' => false, 'message' => 'Empleado no encontrado'], 404);
        }
    
        // Actualizar los datos del empleado
        $employee->update([
            'dni' => $request->dni,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'fecha_vencimiento_seguro' => $request->fecha_vencimiento_seguro,
        ]);
    
        \Log::info('empleadosUpdate - Employee updated successfully');
    
        // Debug: Verificar archivos antes de procesarlos
        \Log::info('empleadosUpdate - Files check:', [
            'has_files_input' => $request->has('files'),
            'files_is_array' => is_array($request->input('files')),
            'files_count' => $request->has('files') ? count($request->input('files', [])) : 0,
            'hasFile_files' => $request->hasFile('files'),
        ]);
    
        // Procesar y agregar nuevos archivos si existen
        if ($request->has('files') && is_array($request->input('files'))) {
            \Log::info('empleadosUpdate - Processing files...');
            
            $files = $request->input('files');
            foreach ($files as $index => $fileData) {
                \Log::info("empleadosUpdate - Processing file {$index}:", [
                    'file_data' => $fileData,
                    'has_file_key' => isset($fileData['file']),
                    'file_type' => isset($fileData['file']) ? gettype($fileData['file']) : 'not_set',
                ]);
    
                // Verificar si el archivo está en el request
                if ($request->hasFile("files.{$index}.file")) {
                    $file = $request->file("files.{$index}.file");
                    \Log::info("empleadosUpdate - File found:", [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
    
                    try {
                        // Guardar el archivo en storage
                        $filePath = $file->store('employee-files', 'public');
                        \Log::info("empleadosUpdate - File stored:", ['path' => $filePath]);
                        
                        // Crear el registro en la base de datos
                        $fileRecord = $employee->files()->create([
                            'name' => $fileData['name'],
                            'file' => $filePath,
                            'fecha_vencimiento' => $fileData['fecha_vencimiento'] ?? null,
                        ]);
                        
                        \Log::info("empleadosUpdate - File record created:", ['record_id' => $fileRecord->id]);
                        
                    } catch (\Exception $e) {
                        \Log::error("empleadosUpdate - Error processing file {$index}:", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    \Log::warning("empleadosUpdate - File not found in request:", ['index' => $index]);
                }
            }
        } else {
            \Log::info('empleadosUpdate - No files to process');
        }
    
        return response()->json(['status' => true, 'message' => 'Empleado actualizado correctamente'], 200);
    }

    /**
     * Configuración utilizada por el formulario móvil. Se comparte con el
     * recurso de Filament para que los documentos requeridos sean los mismos.
     */
    public function empleadosConfiguracion()
    {
        return response()->json([
            'documentos_personales' => $this->mobileDocumentConfiguration('employee'),
            'documentos_vehiculo' => $this->mobileDocumentConfiguration('car'),
            'dias' => ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'],
        ]);
    }

    public function empleadosMovilStore(Request $request)
    {
        $data = $this->mobileEmployeeRequestData($request);
        $owner = $request->user()->owner;

        $employee = DB::transaction(function () use ($request, $data, $owner) {
            $employee = Employee::create([
                'work_id' => 36,
                'dni' => $data['dni'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'observations' => $data['observations'],
                'user_id' => $request->user()->id,
                'owner_id' => $owner->id,
                'model_origen' => 'Owner',
                'model_origen_id' => $owner->id,
                'status' => 'pendiente',
            ]);

            $employee->owners()->syncWithoutDetaching([$owner->id]);
            $this->syncMobileEmployeeRelations($employee, $request, $data, false);

            return $employee;
        });

        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['update_employee'],
            'Nuevo trabajador pendiente de revisión',
            $employee->nombres().' fue registrado y espera la aprobación de administración.',
            ['type' => 'employee', 'employee_id' => $employee->id, 'status' => 'pendiente'],
            \App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee]),
            'heroicon-o-user-plus',
        );

        return response()->json([
            'status' => true,
            'message' => 'Trabajador creado y enviado a aprobación.',
            'empleado' => $employee->load(['autos.files', 'files', 'horarios']),
        ], 201);
    }

    public function empleadosMovilUpdate(Request $request, $id)
    {
        $employee = Employee::where('id', $id)
            ->where('owner_id', $request->user()->owner->id)
            ->with(['autos.files', 'files'])
            ->firstOrFail();

        $data = $this->mobileEmployeeRequestData($request, $employee);

        DB::transaction(function () use ($employee, $request, $data) {
            $employee->update([
                'dni' => $data['dni'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'observations' => $data['observations'],
            ]);

            $this->syncMobileEmployeeRelations($employee, $request, $data, true);
        });

        return response()->json([
            'status' => true,
            'message' => 'Trabajador actualizado correctamente.',
            'empleado' => $employee->fresh()->load(['autos.files', 'files', 'horarios']),
        ]);
    }

    private function mobileEmployeeRequestData(Request $request, ?Employee $employee = null): array
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required', 'digits_between:7,8'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'schedules' => ['nullable', 'string'],
            'vehicles' => ['nullable', 'string'],
            'employee_documents_meta' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $schedules = $this->decodeMobileArray($request->input('schedules'), 'schedules');
        $vehicles = $this->decodeMobileArray($request->input('vehicles'), 'vehicles');
        $employeeDocuments = $this->decodeMobileArray(
            $request->input('employee_documents_meta'),
            'employee_documents_meta'
        );

        $scheduleValidator = Validator::make(['schedules' => $schedules], [
            'schedules' => ['array'],
            'schedules.*.day_of_week' => ['required', 'in:Domingo,Lunes,Martes,Miercoles,Jueves,Viernes,Sabado'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $vehicleValidator = Validator::make(['vehicles' => $vehicles], [
            'vehicles' => ['array'],
            'vehicles.*.marca' => ['required', 'string', 'max:255'],
            'vehicles.*.modelo' => ['required', 'string', 'max:255'],
            'vehicles.*.patente' => ['required', 'string', 'max:255'],
            'vehicles.*.color' => ['nullable', 'string', 'max:255'],
            'vehicles.*.documents' => ['array'],
        ]);

        $validator->after(function ($validator) use ($request, $employee, $employeeDocuments, $vehicles, $scheduleValidator, $vehicleValidator) {
            foreach ([$scheduleValidator, $vehicleValidator] as $nestedValidator) {
                foreach ($nestedValidator->errors()->messages() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }

            $this->validateMobileDocuments(
                $validator,
                $request,
                $employeeDocuments,
                $this->mobileDocumentConfiguration('employee'),
                $employee?->files,
                'employee_documents',
                'documentos personales'
            );

            foreach ($vehicles as $vehicleIndex => $vehicle) {
                $existingFiles = collect();
                if ($employee && !empty($vehicle['id'])) {
                    $existingAuto = $employee->autos->firstWhere('id', $vehicle['id']);
                    $existingFiles = $existingAuto?->files ?? collect();
                }

                $this->validateMobileDocuments(
                    $validator,
                    $request,
                    $vehicle['documents'] ?? [],
                    $this->mobileDocumentConfiguration('car'),
                    $existingFiles,
                    'vehicle_documents',
                    'documentos del vehículo ' . ($vehicleIndex + 1)
                );
            }
        });

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return [
            'dni' => $request->input('dni'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'observations' => $request->input('observations'),
            'schedules' => $schedules,
            'vehicles' => $vehicles,
            'employee_documents' => $employeeDocuments,
        ];
    }

    private function syncMobileEmployeeRelations(Employee $employee, Request $request, array $data, bool $isUpdate): void
    {
        $employee->horarios()->delete();
        foreach ($data['schedules'] as $schedule) {
            $employee->horarios()->create([
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
            ]);
        }

        $this->syncMobileDocuments(
            $employee->files(),
            $request,
            $data['employee_documents'],
            'employee_documents',
            'employee-files'
        );

        $existingAutos = $employee->autos()->with('files')->get()->keyBy('id');
        $incomingIds = collect($data['vehicles'])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        if ($isUpdate) {
            foreach ($existingAutos->except($incomingIds) as $auto) {
                $auto->files()->delete();
                $auto->delete();
            }
        }

        foreach ($data['vehicles'] as $vehicle) {
            $auto = !empty($vehicle['id']) ? $existingAutos->get((int) $vehicle['id']) : null;
            if ($auto) {
                $auto->update([
                    'marca' => $vehicle['marca'],
                    'modelo' => $vehicle['modelo'],
                    'patente' => $vehicle['patente'],
                    'color' => $vehicle['color'] ?? null,
                ]);
            } else {
                $auto = $employee->autos()->create([
                    'marca' => $vehicle['marca'],
                    'modelo' => $vehicle['modelo'],
                    'patente' => $vehicle['patente'],
                    'color' => $vehicle['color'] ?? null,
                    'user_id' => $request->user()->id,
                    'model' => 'Employee',
                    'model_id' => $employee->id,
                ]);
            }

            $this->syncMobileDocuments(
                $auto->files(),
                $request,
                $vehicle['documents'] ?? [],
                'vehicle_documents',
                'auto-files'
            );
        }
    }

    private function syncMobileDocuments($relation, Request $request, array $documents, string $uploadKey, string $directory): void
    {
        foreach ($documents as $document) {
            $name = $document['name'] ?? 'Documento';
            $record = !empty($document['id'])
                ? $relation->where('id', $document['id'])->first()
                : $relation->where('name', $name)->first();
            $file = isset($document['file_index'])
                ? $request->file($uploadKey . '.' . $document['file_index'])
                : null;

            if ($file) {
                $path = $file->store($directory, 'public');
                $attributes = [
                    'name' => $name,
                    'file' => $path,
                    'fecha_vencimiento' => $document['expires_at'] ?? null,
                ];
                $record ? $record->update($attributes) : $relation->create($attributes);
            } elseif ($record && array_key_exists('expires_at', $document)) {
                $record->update(['fecha_vencimiento' => $document['expires_at'] ?: null]);
            }
        }
    }

    private function validateMobileDocuments($validator, Request $request, array $documents, array $configuration, $existingFiles, string $uploadKey, string $label): void
    {
        foreach ($configuration as $requiredDocument) {
            $document = collect($documents)->firstWhere('name', $requiredDocument['name']);
            $hasExisting = collect($existingFiles)->contains('name', $requiredDocument['name']);
            $hasNewFile = $document && isset($document['file_index'])
                && $request->hasFile($uploadKey . '.' . $document['file_index']);

            if ($requiredDocument['is_required'] && !$hasExisting && !$hasNewFile) {
                $validator->errors()->add($uploadKey, 'Adjuntá el documento requerido: ' . $requiredDocument['name'] . ' (' . $label . ').');
            }

            if ($requiredDocument['requires_expiry'] && empty($document['expires_at'] ?? null)) {
                $validator->errors()->add($uploadKey, 'Indicá el vencimiento de ' . $requiredDocument['name'] . ' (' . $label . ').');
            }
        }
    }

    private function mobileDocumentConfiguration(string $type): array
    {
        $required = FilesRequired::where('type', $type)->first()?->required ?? [];
        $documents = collect($required)->map(fn ($document) => [
            'name' => $document['document'] ?? $document['name'] ?? 'Documento',
            'is_required' => in_array($document['is_required'] ?? false, [true, 1, '1'], true),
            'requires_expiry' => in_array($document['date_is_required'] ?? false, [true, 1, '1'], true),
        ])->values()->all();

        if ($type === 'car' && empty($documents)) {
            return [
                ['name' => 'Seguro del Vehículo', 'is_required' => false, 'requires_expiry' => false],
                ['name' => 'VTV', 'is_required' => false, 'requires_expiry' => false],
                ['name' => 'Cédula del Vehículo', 'is_required' => false, 'requires_expiry' => false],
            ];
        }

        return $documents;
    }

    private function decodeMobileArray(?string $value, string $field): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => ['La información enviada no tiene un formato válido.'],
            ]);
        }

        return $decoded;
    }





    public function deleteEmpleados(Request $request)
    {
        Employee::where('id',$request->id)->delete();
        return response()->json(['status' => true, 'message' => 'Registro eliminado' ], 200);
    }

    public function spontaneous_visit(Request $request)
    {

        $visitantes = OwnerSpontaneousVisit::where('owner_id', $request->user()->owner->id)
                        ->where('aprobado',null)
                        ->where('agregado',null)
                        ->get();

        $data = $visitantes->map(function($visitante){
            $visitante['texto'] = $visitante['first_name']." ". $visitante['last_name'];
            return $visitante;
        })->pluck('texto','id');

        return  count($data) ? $data : (object)[] ;

    }

    public function spontaneous_visit_action(Request $request)
    {
        $visitante = OwnerSpontaneousVisit::where('owner_id', $request->user()->owner->id)
                        ->where('id',$request->id)
                        ->where('agregado',null)
                        ->first();

        if($visitante){
            $visitante->aprobado = $request->status;
            if($request->status == 0){
                $visitante->agregado = 0;
            }
            $visitante->save();
        }

        $visitantes = OwnerSpontaneousVisit::where('owner_id', $request->user()->owner->id)
                        ->where('aprobado',null)
                        ->where('agregado',null)
                        ->get();

        $data = $visitantes->map(function($visitante){
            $visitante['texto'] = $visitante['first_name']." ". $visitante['last_name'];
            return $visitante;
        })->pluck('texto','id');

        return  count($data) ? $data : (object)[] ;
    }

    public function getPopUp(Request $request)
    {
        $popup = PopUp::where('active',1)->orderBy('created_at','desc')->first();
		if($popup){
            $popup->image = $popup->image ? request()->getSchemeAndHttpHost().Storage::url($popup->image) : $popup->image;
            $popup->makeHidden(['created_at','updated_at','id','active']);
		}
		return response()->json($popup );
    }

    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'email' => 'required|email:rfc,dns',
            'phone' => 'required|numeric',
            'body'  => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json( ['status' => false, 'errors' => $validator->errors() ], 422);
        }

        try {
            Mail::to(config('app.mail_recibe_mensaje'))->send(new Contact($request->all()));
        } catch (\Throwable $th) {
            return response()->json( [ 'status' => false, 'message' => $th->getMessage() ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Mensaje enviado' ]);
    }

    public function newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|unique:newsletters',
        ]);

        if ($validator->fails()) {
            return response()->json( ['status' => false, 'errors' => $validator->errors() ], 422);
        }

        try {
            Newsletter::insert([
                'email'=> $request->email,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        } catch (\Throwable $th) {
            return response()->json( [ 'status' => false, 'message' => $th->getMessage() ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Email guardado' ]);
    }

    public function messenger(Request $request)
    {

        $socialMessages = new SocialMessages();

        $conversations = $socialMessages->getConversations();

        dd($conversations);

        // $urltoken = "https://graph.facebook.com/oauth/access_token?client_id=1135220454605588&client_secret=85d5e99eca4a924916356a1e4cce4dee&grant_type=client_credentials&scope=pages_show_list";

        // $auth = Http::get($urltoken);

        // $auth = $auth->collect();



        // $urlaccount = "https://graph.facebook.com/1135220454605588/accounts?access_token=".$auth['access_token'];

        // $account = Http::get($urlaccount);

        // dd($account->body());

        // dd($auth);
        // $accesToken = "EAAQIehvwQxQBOz0ZBiyrMlny8HxTs43Kt4v5jk3ZA9Un71ruNsU9AUXgD6GiRqeQbi2M9nsVgfr2vC2ZCtAdJC6UZCDX5oPzULy6xtaG34LH2LYIeJwjYPH0O7xSrdTl7lOSyxueCqx1mM2oILYbC9UOf5v0RpBQ2TRuWS9rvoK5IM5iHhxR2NugVDyKnZAOQUtJlDpFkKbE9ZBEUUXSuo";

        // $url = "https://graph.facebook.com/v21.0/me/conversations?access_token=".$accesToken; //TODAS LAS CONVERSACIONES
        // $url = "https://graph.facebook.com/v17.0/1135220454605588/conversations?access_token=".$auth['access_token'];

        // $messages = Http::get($url);

        // dd($messages->collect());

        // $urlMessage = "https://graph.facebook.com/v21.0/t_10231717396494502?fields=messages&access_token=".$accesToken;

        // $message = Http::get($urlMessage); // UNA SOLA CONVERSACION

        // dd($message->collect());

        // $URLmE = "https://graph.facebook.com/v21.0/m_-ZZS2yRxZhgxWJAXYTQlq48XvQE0W5KZt2yhhzJ67aXFSrw1q2McHlXjHHSG9s_bfTZHoyeu6O5FpETgjIY9Qw?fields=id,created_time,from,to,message&access_token=".$accesToken;

        // $emessage = Http::get($URLmE); //UN MENSAJE

        // dd($emessage->collect());
    }

    public function landing($slug)
    {
        return response()->json(Landing::with(['imagenes','campos'])->where('slug',$slug)->first());
    }

    public function landingsend(Request $request)
    {
        \Log::info($request->all());


        try {
            //code...
            LandingData::insert([
                'data' => json_encode($request->all()),
                'landing_id' => $request->landing_id
            ]);

            $this->sendMailLanding([
                'data' => $request->all(),
                'landing' => Landing::with(['imagenes','campos'])->where('id',$request->landing_id)->first()
            ]);

        } catch (\Throwable $th) {
           return response()->json(['status'=> false, 'message'=> $th->getMessage() ], 422);
        }

        return response()->json(['status'=> true,'message'=>'Registro almacenado']);
    }

    private function sendMailLanding($data)
    {

        try {
            Mail::to(config('app.mail_landing'))->send(new sendMailLanding($data));
        } catch (\Throwable $th) {
            return response()->json( [ 'status' => false, 'message' => $th->getMessage() ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Mensaje enviado' ]);
    }

    public function getOwner(Request $request)
    {
        $owner = Owner::where('id', $request->user()->owner->id)->first();
        // Convert all null values (including nested arrays/objects) to empty strings
        $owner = json_decode(json_encode($owner), true);
        array_walk_recursive($owner, function (&$value) {
            if (is_null($value)) {
              $value = '';
            }
        });

        return response()->json( $owner  , 200);
    }
}
