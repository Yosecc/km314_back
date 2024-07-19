<?php

namespace App\Http\Controllers\Api;

use App\Models\Lote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Lotes extends Controller
{
    public function index(Request $request)
    {
        $misLotes = Lote::where('owner_id', $request->user()->owner->id)->with(['sector','loteStatus','loteType'])->get();
        
        return response()->json($misLotes);
    }
}
