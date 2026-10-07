<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TerminosCondiciones;
use Illuminate\Http\Request;

class MobileTermsConditionsController extends Controller
{
    public function show(Request $request)
    {
        $terms = TerminosCondiciones::query()->first();

        return response()->json([
            'accepted' => (bool) $request->user()->is_terms_condition,
            'terms' => [
                'title' => $terms?->titulo ?? 'Términos y condiciones',
                'content' => $terms?->contenido ?? 'Para continuar utilizando KM314 Casas de Mar, leé y aceptá los términos y condiciones.',
            ],
        ]);
    }

    public function accept(Request $request)
    {
        $user = $request->user();
        $user->forceFill(['is_terms_condition' => true])->save();

        return response()->json([
            'accepted' => true,
            'message' => 'Términos y condiciones aceptados.',
        ]);
    }
}
