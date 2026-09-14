<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestFile;
use App\Models\ServiceRequestNote;
use App\Models\ServiceRequestResponsiblePeople;
use App\Models\ServiceRequestType;
use App\Models\ServiceRequestStatus;
use App\Models\Lote;
use App\Models\User;
use App\Services\ApplicationNotificationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use function Livewire\store;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Actions\Action;
class Solicitudes extends Controller
{

    private function _getSolicitudes($solicitudes)
    {
        $solicitudes = $solicitudes->map(function($solicitud){
            $solicitud['starts_at'] = $solicitud['starts_at']
                ? Carbon::parse($solicitud['starts_at'])->format('Y-m-d H:i:s')
                : null;
            $solicitud['ends_at'] = $solicitud['ends_at']
                ? Carbon::parse($solicitud['ends_at'])->format('Y-m-d H:i:s')
                : null;

            if($solicitud->responsible){
                $solicitud->responsible->makeHidden(['created_at','updated_at']);
            }
            if($solicitud->serviceRequestFile){
                $solicitud->serviceRequestFile->map(function($archivo){
                    // $archivo['file'] = config('app.url').Storage::url($archivo['file']);
                    $archivo['path'] = request()->getSchemeAndHttpHost().Storage::url($archivo['file']);
                    $archivo['description'] = $archivo['description'] ?? '';
                    $archivo['name'] = $archivo['description'];
					$archivo['fileName'] = $archivo['attachment_file_names'];
                    unset($archivo['file'] );
                    return $archivo;
                });
            }

            return $solicitud;
        });

        return $solicitudes;
    }

    public function index(Request $request)
    {
        $solicitudes = ServiceRequest::where('owner_id',$request->user()->owner->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestFile','serviceRequestNote'])
                            ->orderBy('created_at','desc')
                            ->get();

        $solicitudes = $this->_getSolicitudes($solicitudes);

        return response()->json($solicitudes);
    }

    public function getProximasSolicitudes(Request $request)
    {
        $solicitudes = ServiceRequest::where('owner_id', $request->user()->owner->id)
        ->with(['serviceRequestStatus', 'serviceRequestType', 'service', 'lote', 'responsible', 'serviceRequestFile', 'serviceRequestNote'])
        ->orderBy('created_at', 'desc')
        ->orderBy('ends_at', 'desc')
        ->get();

        $solicitudes = $this->_getSolicitudes($solicitudes);

        $now = Carbon::now();
        $tiposSolicitudes = ServiceRequestType::all();

        $solicitudes = $solicitudes->map(function ($item) use ($now) {
            $item->starts_at_date = Carbon::parse($item->starts_at);

            $item->ends_at_date = $item->ends_at ? Carbon::parse($item->ends_at) : $item->starts_at_date;

            $item->is_active = $now->between($item->starts_at_date, $item->ends_at_date);
            $item->is_future = $item->starts_at_date->isFuture();

            $item->time_left = $item->is_active
                ? $now->diffForHumans($item->ends_at_date, true)
                : ($item->is_future
                    ? $now->diffForHumans($item->starts_at_date, true)
                    : $item->ends_at_date->diffForHumans($now, true)
                );

            if ($item->is_future) {
                $item->is_active = true;
            }

            return $item;
        })
        ->where('is_active', true)
        ->where('service_request_status_id', 2)
        // ->sortBy(function ($item) {
        //     return $item->starts_at_date->getTimestamp(); // Ordenar por fecha
        // })
        ->groupBy('service_request_type_id')
        // ->map(function ($grupo) {
        //     return $grupo->sortBy(function ($item) {
        //         return $item->starts_at_date->getTimestamp(); // Ordenar por fecha dentro del grupo
        //     });
        // })
        ->mapWithKeys(function ($value, $key) use ($tiposSolicitudes) {
            $tipo = $tiposSolicitudes->where('id', $key)->first();
            return $tipo ? [$tipo->name => $value] : [$key => $value];
        })
        // ->toArray()
        ;



        return response()->json($solicitudes);
    }

    public function store(Request $request)
    {

        $data = $request->data;
        $data = json_decode($request->data, true);

        $validator = Validator::make($data, [
            //"service_request_status_id" => 'nullable',
            //"service_request_responsible_people_id" => 'nullable',
            "lote_id" => 'required',
            //"service_request_type_id" => 'required',
            "service_id" => 'required',
            "model" => 'nullable',
            "model_id" => 'nullable',
            "options" => 'nullable|array',
            "name" => 'required',
            "starts_at" => 'required',
            "ends_at" => 'nullable',
            "observations" => 'nullable'
        ], [], [
            // Atributos personalizados
            'lote_id' => 'ID de lote',
            'propertie_id' => 'propiedad',
            'service_request_type_id' => 'tipo',
            'service_id' => 'servicio',
            'model' => 'propietario',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'name' => 'nombre',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! Lote::query()
            ->whereKey($data['lote_id'])
            ->where('owner_id', $request->user()->owner->id)
            ->exists()) {
            return response()->json([
                'lote_id' => ['El lote seleccionado no pertenece a tu perfil.'],
            ], 422);
        }

        $datos = function($request){

            $service = Service::find($request['service_id']);
            $startsAt = Carbon::parse($request['starts_at']);
            $endsAt = !empty($request['ends_at'])
                ? Carbon::parse($request['ends_at'])
                : null;

            if (! $endsAt && $service?->serviceRequestType?->isCalendar) {
                $endsAt = $startsAt->copy()->addHour();
            }

            return [
                //'alias' => $request['alias'],
                'name' => $request['name'],
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt?->format('Y-m-d H:i:s'),
                'service_request_responsible_people_id' => isset($request['service_request_responsible_people_id']) ? $request['service_request_responsible_people_id'] : null,
                'service_request_status_id' => isset($request['service_request_status_id']) ? $request['service_request_status_id'] : 1,
                'service_request_type_id' => $service && $service->service_request_type_id ? $service->service_request_type_id : (isset($request['service_request_type_id']) ? $request['service_request_type_id'] : 1),
                'service_id' => $request['service_id'],
                'lote_id' => $request['lote_id'],
                'owner_id' => $request['owner_id'],
                'model' => $service?->model ?? $request['model'],
                'model_id' => $request['model_id'],
                'options' => $request['options'] ?? [],
                'observations' => $request['observations']
            ];
        };

		// dd($datos);
           $data = $request->data;
        $data = json_decode($request->data, true);

        if(isset($data['id'])){
            $id = $data['id'];
            $d = $datos($data);

            $solicitud = ServiceRequest::query()->visibleTo($request->user())->findOrFail($id);
            $d['owner_id'] = $solicitud->owner_id;
            $d['service_request_status_id'] = $solicitud->service_request_status_id;
            $d['user_id'] = $request->user()->id;
            $solicitud->update($d);

        }else{
            $d = $datos($data);
            $d['owner_id'] = $request->user()->owner->id;
            $d['user_id'] = $request->user()->id;
            $d['service_request_status_id'] = ServiceRequestStatus::defaultPending()->id;
            $solicitud = ServiceRequest::create($d);
            $id = $solicitud->id;
        }

        $solicitud = ServiceRequest::find($id);



        if(isset($data['responsible'])){
            if(isset($data['responsible']['id'])){
                $data['responsible']['updated_at'] = Carbon::now();
                ServiceRequestResponsiblePeople::where('id',$data['responsible']['id'])->update($data['responsible']);
            }else{
                $data['responsible']['created_at'] = Carbon::now();
                $data['responsible']['updated_at'] = Carbon::now();
                $responsible = ServiceRequestResponsiblePeople::insertGetId($data['responsible']);
                $responsible = ServiceRequestResponsiblePeople::find($responsible);
                $solicitud->responsible()->associate($responsible);
                $solicitud->save();
            }
        }




           if ($request->hasFile('files')) {
    $files_detail = $request->files_detail;
    foreach ($request->file('files') as $key => $file) {
        // Guardar el archivo en el disco 'public'
        $documento = is_string($files_detail[$key]) ? json_decode($files_detail[$key]) : $files_detail[$key];

        if (isset($documento->id) && $documento->id != null) {
            \Log::info(['QUE HAY EN EL FILE AL EDITAR?', $documento, $file]);
            // $file = ServiceRequestFile::where('id', $documento->id)->update([
            //     'attachment_file_names' => $file->getClientOriginalName(),
            //     'file' => $path,
            //     'updated_at' => Carbon::now(),
            //     'user_id' => $request->user()->id,
            //     'description' => $documento->description
            // ]);
        } else {
            $path = $file->store('', 'public');
            \Log::info(['////////',$solicitud]);

            ServiceRequestFile::insert([
                'attachment_file_names' => $file->getClientOriginalName(),
                'file' => $path,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id' => $request->user()->id,
                'description' => $documento->description,
				 'service_request_id' => $solicitud->id
            ]);
        }
    }
}

       $solicitud = ServiceRequest::where('id',$id)
        ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestNote','serviceRequestFile'])
        ->get();

       $solicitud = $this->_getSolicitudes($solicitud);


        return response()->json($solicitud->first());

    }

    public function file(Request $request)
    {

        \Log::info($request->all());
        $validator = Validator::make($request->all(), [
            "id" => 'required',
            "file" => 'required',
            "description" => 'nullable',
            "file_id" => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $solicitud = ServiceRequest::query()->visibleTo($request->user())->find($request->id);

        if (!$solicitud) {
            return response()->json(['No existe la socitud'], 422);
        }

        abort_unless(! $request->user()->hasRole('owner') || $solicitud->isEditableByOwner(), 403);

        if(isset($request->file_id) && $request->file_id){
            $file = ServiceRequestFile::query()->whereKey($request->file_id)
                ->where('service_request_id', $solicitud->id)->update([
                'description' => $request->description,
                'updated_at' => Carbon::now()
            ]);
        }else{
            if($solicitud){
                $data = [];

                $data['created_at'] = Carbon::now();
                $data['updated_at'] = Carbon::now();

                $path = null;
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('', 'public');
                }

                // $path = Storage::putFile('', new File($request->file),'public');

                $data['file'] = $path;
                $data["description"] = $request->description;
                $data['user_id'] = $request->user()->id;

                $solicitud->serviceRequestFile()->create($data);
                $solicitud->save();
            }
        }

        $solicitud = ServiceRequest::query()->visibleTo($request->user())->where('id',$request->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestNote','serviceRequestFile'])
                            ->get();

        $solicitud = $this->_getSolicitudes($solicitud);


        return response()->json($solicitud->first());
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = ServiceRequestFile::query()->whereKey($request->id)
            ->whereHas('serviceRequest', fn ($query) => $query->visibleTo($request->user()))
            ->first();

        if(!$file){
            return response()->json(['No existe archivo'], 422);
        }

        abort_unless(! $request->user()->hasRole('owner') || $file->serviceRequest?->isEditableByOwner(), 403);

        if(Storage::disk('public')->exists($file->file)){
            Storage::disk('public')->delete($file->file);
        }

        $file->delete();

        return response()->json(true);
    }

    public function addNota(Request $request)
    {

        $request->validate([
            'service_request_id' => 'required',
            'nota' => 'required|max:250'
        ]);

        $serviceRequest = ServiceRequest::query()->visibleTo($request->user())->findOrFail($request->service_request_id);

        $nota = new ServiceRequestNote();
        $nota->service_request_id = $request->service_request_id;
        $nota->user_id = $request->user()->id;
        $nota->description = $request->nota;
        $nota->save();

        $notas = ServiceRequestNote::where('service_request_id',$request->service_request_id)
                    ->with('user')
                    ->get();

        app(ApplicationNotificationService::class)->sendToAdministrativePermissionHolders(
            ['view_any_service::request', 'update_service::request'],
            'Nueva nota en una solicitud',
            sprintf('#%d · %s', $serviceRequest->id, $request->nota),
            ['type' => 'service_request', 'service_request_id' => $serviceRequest->id, 'event' => 'note_created'],
            \App\Filament\Resources\ServiceRequestResource::getUrl('edit', ['record' => $serviceRequest]),
            'heroicon-o-wrench-screwdriver',
        );

        return response()->json($notas);
    }
}
