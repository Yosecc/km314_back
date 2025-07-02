<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Logic\Providers\FacebookRepository;

class SocialController extends Controller
{

    protected $facebook;

    public function __construct()
    {
        $this->facebook = new FacebookRepository();
    }

    public function redirectToProvider()
    {
        // Redirección a Facebook deshabilitada temporalmente
        // return redirect($this->facebook->redirectTo());

        // Retorna una respuesta simple en lugar de redirigir
        return response('Facebook login temporalmente deshabilitado', 200);
    }

    public function handleProviderCallback(Request $request)
    {
        // Callback de Facebook deshabilitado temporalmente
        // $accessToken = $this->facebook->handleCallback();
        // $value = Cache::store('file')->put('access_token', $accessToken);
        // $this->facebook->getPages($accessToken);
        // return redirect()->route('filament.admin.pages.messages');

        // Retorna una respuesta simple en lugar de redirigir
        return response('Facebook callback temporalmente deshabilitado', 200);
    }

    public function facebook_webhook(Request $request)
    {
        // Webhook deshabilitado temporalmente
        // \Log::debug($request->all());
        // $request = $request->all();

        // $mode = $request['hub_mode'];
        // $challenge = $request['hub_challenge'];
        // $token = $request['hub_verify_token'];

        // if($mode && $token){
            // if($mode == 'subscribe' && $token == 'TOKENWEBHOOK'){
                return response($request->input('hub_challenge'),200);
        //     }else{
        //         return response()->json('Invalid token',403);
        //     }
        // }
    }

    public function facebook_webhook_post(Request $request)
    {
        // Webhook POST deshabilitado temporalmente
        // \Log::debug($request->all());
        // if ($request->body['object'] === "page") {
        //     // Returns a '200 OK' response to all requests
        //     return response("EVENT_RECEIVED", 200);
        // }

        // Solo retorna 200 OK para no romper Facebook
        return response("OK", 200);
    }

}
