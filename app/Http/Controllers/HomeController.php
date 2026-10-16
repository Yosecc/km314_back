<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getTerminosCondicionesFormControl(Request $request)
    {

        $formControl = $request->query('id', false);

        $terminosCondiciones = \App\Models\TerminosCondiciones::find($formControl);
        return view('terminos-condiciones-form-control', compact('terminosCondiciones'));
    }

    public function getPoliticaPrivacidad()
    {
        $terminosCondiciones = \App\Models\TerminosCondiciones::findOrFail(2);

        return view('terminos-condiciones-form-control', compact('terminosCondiciones'));
    }
}
