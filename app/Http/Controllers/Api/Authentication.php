<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Authentication extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ],[],['password' => 'Contraseña']);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
 
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ], 422);
            
        }
    
        return response()->json(['token'=>$user->createToken($request->device_name)->plainTextToken]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'SesiÃ³n cerrada correctamente.',
        ]);
    }
}
