<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestFile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\ServiceRequestResponsiblePeople;

use function Livewire\store;

class Solicitudes extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = ServiceRequest::where('owner_id',$request->user()->owner->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestFile','serviceRequestNote'])
                            ->orderBy('created_at','desc')
                            ->get();

        $solicitudes = $solicitudes->map(function($solicitud){
            if($solicitud->responsible){
                $solicitud->responsible->makeHidden(['created_at','updated_at']);
            }
            if($solicitud->serviceRequestFile){

                $solicitud->serviceRequestFile->map(function($archivo){
                    //  dd(storage_path($archivo['file']));
                    $archivo['file'] = asset(Storage::url($archivo['file']));
                    return $archivo;
                });
            }
            
            return $solicitud;
        });

        return response()->json($solicitudes);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            "service_request_status_id" => 'nullable',
            "service_request_responsible_people_id" => 'nullable',
            "lote_id" => 'nullable',
            "service_request_type_id" => 'required',
            "service_id" => 'required',
            "model" => 'nullable',
            "model_id" => 'nullable',
            "options" => 'nullable',
            "name" => 'required',
            "starts_at" => 'required',
            "ends_at" => 'required',
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

        $datos = function($request){
            return [
                'alias' => $request['alias'],
                'name' => $request['name'], 
                'starts_at' => $request['starts_at'],
                'ends_at' => $request['ends_at'],
                'service_request_responsible_people_id' => isset($request['service_request_responsible_people_id']) ? $request['service_request_responsible_people_id'] : null,
                'service_request_status_id' => $request['service_request_status_id'],
                'service_request_type_id' => $request['service_request_type_id'],
                'service_id' => $request['service_id'],
                'lote_id' => $request['lote_id'],
                'owner_id' => $request['owner_id'],
                'model' => $request['model'],
                'model_id' => $request['model_id'],
                'options' => json_encode($request['options']),
                'observations' => $request['observations']
            ];
        };

        $data = $request->all();

        if(isset($data['id'])){
            $id = $data['id'];
            $d = $datos($data);

            $d['user_id'] = $request->user()->id;
            $d['updated_at'] = Carbon::now();

            $solicitud = ServiceRequest::where('id', $id)->update($d);

        }else{

            $d = $datos($data);
            $d['owner_id'] = $request->user()->owner->id;
            $d['user_id'] = $request->user()->id;
            $d['created_at'] = Carbon::now();
            $d['updated_at'] = Carbon::now();
            
            $id = ServiceRequest::insertGetId($d);
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
        
        $solicitud = ServiceRequest::where('id',$id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestNote','serviceRequestFile'])
                            ->first();

        return response()->json($solicitud);

    }

    public function file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => 'required',
            "file" => 'required',
            "description" => 'nullable',
            "file_id" => 'nullable'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $solicitud = ServiceRequest::where('id',$request->id)->first();

        if (!$solicitud) {
            return response()->json(['No existe la socitud'], 422);
        }
        
        if(isset($request->file_id) && $request->file_id){
            $file = ServiceRequestFile::where('id',$request->file_id)->update([
                'description' => $request->description,
                'updated_at' => Carbon::now()
            ]);
        }else{
            if($solicitud){
                $data = [];

                $data['created_at'] = Carbon::now();
                $data['updated_at'] = Carbon::now();

                $path = Storage::putFile('service_request_files', new File($request->file),'public');
                
                $data['file'] = $path;
                $data["description"] = $request->description;
                $data['user_id'] = $request->user()->id;

                $solicitud->serviceRequestFile()->create($data);
                $solicitud->save();
            }
        }

        $solicitud = ServiceRequest::where('id',$request->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestNote','serviceRequestFile'])
                            ->first();

        return response()->json($solicitud);
    }

    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = ServiceRequestFile::where('id', $request->id)->first();

        if(!$file){
            return response()->json(['No existe archivo'], 422);
        }

        if(Storage::disk('public')->exists($file->file)){
            Storage::disk('public')->delete($file->file);
        }
        
        $file->delete();

        return response()->json(true);
    }
}
