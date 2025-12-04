<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getTerminosCondicionesFormControl()
    {

        $terminosCondiciones = \App\Models\TerminosCondiciones::first();
        return view('terminos-condiciones-form-control', compact('terminosCondiciones'));
    }
}
