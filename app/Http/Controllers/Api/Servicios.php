<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;
use App\Models\StartUp;
use App\Models\CommonSpaces;
use Illuminate\Http\Request;
use App\Models\StartUpOption;
use App\Models\HomeInspection;
use App\Models\RentalAttention;
use App\Http\Controllers\Controller;
use App\Models\WorksAndInstallation;

class Servicios extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['serviceType'])->get();
        
        return response()->json($services->groupBy('service_type_id')->map(function($grupo){
            return [
                'id' => $grupo[0]->service_type_id,
                'name' => $grupo[0]->serviceType->name,
                'items' => $grupo,
            ];
        })->values()->all());
    }

    public function combox()
    {
        $data = [
            'RentalAttention' => RentalAttention::get()->pluck('name','id')->toArray(),
            'HomeInspection' => HomeInspection::get()->pluck('name','id')->toArray(),
            'WorksAndInstallation' => WorksAndInstallation::get()->pluck('name','id')->toArray(),
            'CommonSpaces' => CommonSpaces::get()->pluck('name','id')->toArray(),
            'StartUp' => StartUp::get()->pluck('name','id')->toArray(),
            'StartUpOption' => StartUpOption::get()->pluck('name','id')->toArray(),
        ];

        return response()->json($data);
    }
}
