<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Servicios extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['serviceType'])->get();
        
        return response()->json($services);
    }
}
