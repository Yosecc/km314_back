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
        return redirect($this->facebook->redirectTo());
    }

    public function handleProviderCallback(Request $request)
    {
        $accessToken = $this->facebook->handleCallback();

        $value = Cache::store('file')->put('access_token', $accessToken);
        // $this->facebook->getPages($accessToken);
       
        return redirect()->route('filament.admin.pages.messages');
    }

    public function facebook_webhook(Request $request)
    {
        \Log::debug($request->all());
        $request = $request->all();

        $mode = $request['hub_mode'];
        $challenge = $request['hub_challenge'];
        $token = $request['hub_verify_token']; 

        if($mode && $token){
            if($mode == 'subscribe' && $token == 'TOKENWEBHOOK'){
                return response()->json($challenge,200);
            }else{
                return response()->json('Invalid token',403);
            }
        }
    }

}
