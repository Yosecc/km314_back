<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getTerminosCondicionesFormControl(Request $request)
    {

        $formControl = $request->query('form_control', false);

        $terminosCondiciones = \App\Models\TerminosCondiciones::find($formControl);
        return view('terminos-condiciones-form-control', compact('terminosCondiciones'));
    }
}
