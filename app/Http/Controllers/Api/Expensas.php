<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Expensas extends Controller
{
    public function index(Request $request)
    {
        $expensas = Expense::where('owner_id', $request->user()->owner->id)
                        ->with(['expenseStatus','lote','concepts','propertie'])
                        ->get();
        
        return response()->json($expensas);
    }
}
