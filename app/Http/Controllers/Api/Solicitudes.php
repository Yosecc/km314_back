<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class Solicitudes extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = ServiceRequest::where('owner_id',$request->user()->owner->id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','propertie','serviceRequestFile','serviceRequestNote'])
                            ->orderBy('starts_at','desc')
                            ->get();
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
        $id = ServiceRequest::insertGetId($data);
        
        $solicitud = ServiceRequest::where('id',$id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','propertie','responsible','serviceRequestNote','serviceRequestFile'])
                            ->first();

        return response()->json($solicitud);

    }
}
