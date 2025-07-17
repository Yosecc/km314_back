<?php

namespace App\Http\Controllers\Api;

use App\Models\Lote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Lotes extends Controller
{
    public function index(Request $request)
    {
        $misLotes = Lote::where('owner_id', $request->user()->owner->id)
            ->with(['sector','loteStatus','loteType'])
            ->get()
            ->map(function($lote) {
                $arr = $lote->toArray();
                array_walk_recursive($arr, function (&$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                });
                return $arr;
            });

        return response()->json($misLotes);
    }
}
