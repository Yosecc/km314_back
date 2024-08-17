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
            $solicitud->serviceRequestFile->map(function($archivo){
            //  dd(storage_path($archivo['file']));
                $archivo['file'] = config('app.url').Storage::url($archivo['file']);
                return $archivo;
            });
            
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

        $data = $request->all();
        $data['owner_id'] = $request->user()->owner->id;
        $data['created_at'] = Carbon::now();
        $data['updated_at'] = Carbon::now();

        $id = ServiceRequest::insertGetId(collect([$data])->map(function($item){
            unset($item['responsible']);
            unset($item['service_request_file']);
            return $item;
        })->collapse()->toArray());
        
        $solicitud = ServiceRequest::find($id);

        if(isset($data['responsible'])){
            $data['responsible']['created_at'] = Carbon::now();
            $data['responsible']['updated_at'] = Carbon::now();
            $responsible = ServiceRequestResponsiblePeople::insertGetId($data['responsible']);
            $responsible = ServiceRequestResponsiblePeople::find($responsible);
            $solicitud->responsible()->associate($responsible);
            $solicitud->save();
        }
        
        // dd($data);
        // if(isset($data['service_request_file']) && isset($data['service_request_file']['file']) ){
        
       
        //     // ServiceRequestFile::insert($data['service_request_file']);
        // }
        
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
            "observations" => 'nullable'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

    
        $solicitud = ServiceRequest::where('id',$request->id)->first();

        if($solicitud){
            $data = [];
            $data['created_at'] = Carbon::now();
            $data['updated_at'] = Carbon::now();

            $path = Storage::putFile('service_request_files', new File($request->file));
            
            $data['file'] = $path;
            $data["observations"] = $request->observations;
            $data['user_id'] = $request->user()->id;

            $solicitud->serviceRequestFile()->create($data);
            $solicitud->save();
        }

        $solicitud = ServiceRequest::where('id',$request->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','responsible','serviceRequestNote','serviceRequestFile'])
                            ->first();

        

        return response()->json($solicitud);
    }
}
