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
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','propertie'])
                            ->orderBy('starts_at','desc')
                            ->get();
        return response()->json($solicitudes);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "service_request_status_id" => 'nullable',
            "lote_id" => 'nullable',
            "propertie_id" => 'nullable',
            "service_request_type_id" => 'required',
            "service_id" => 'required',
            "owner_id" => 'required',
            "name" => 'required',
            "starts_at" => 'required',
            "ends_at" => 'required',
        ], [], [
            // Atributos personalizados
            'lote_id' => 'ID de lote',
            'propertie_id' => 'propiedad',
            'service_request_type_id' => 'tipo',
            'service_id' => 'servicio',
            'owner_id' => 'propietario',
            'starts_at' => 'fecha de inicio',
            'ends_at' => 'fecha de fin',
            'name' => 'nombre',

        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $id = ServiceRequest::insertGetId($request->all());

        $solicitud = ServiceRequest::where('id',$id)
                            ->with(['serviceRequestStatus','serviceRequestType','service','lote','propertie'])
                            ->first();

        return response()->json($solicitud);

    }
}
