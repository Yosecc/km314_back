<?php

namespace App\Http\Controllers\Api;

use App\Mail\Contact;
use App\Models\Slider;
use App\Models\Landing;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SocialMessages;
use Illuminate\Support\Facades\Validator;


class Main extends Controller
{
    public function sliders()
    {
        
        return [
            'https://kilometro314.com/images/casas/30.jpg',
            'https://kilometro314.com/images/architect/home.jpg',
            'https://kilometro314.com/images/casas/5.jpg',
            'https://kilometro314.com/images/casas/6.jpg',
            'https://kilometro314.com/images/casas/7.jpg',
            
        ];
        return Slider::where('status',1)->get()->map(function($slide){
            
            $slide['img'] =  config('app.url').Storage::url($slide['img']);

            return $slide;

        })->pluck('img');

    }

    public function contact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|max:255',
            'email' => 'required|email:rfc,dns',
            'phone' => 'required|numeric',
            'body'  => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json( ['status' => false, 'errors' => $validator->errors() ], 422);
        }

        try {
            Mail::to(config('app.mail_recibe_mensaje'))->send(new Contact($request->all()));
        } catch (\Throwable $th) {
            return response()->json( [ 'status' => false, 'message' => $th->getMessage() ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Mensaje enviado' ]);
    }

    public function newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|unique:newsletters',
        ]);

        if ($validator->fails()) {
            return response()->json( ['status' => false, 'errors' => $validator->errors() ], 422);
        }

        try {
            Newsletter::insert([
                'email'=> $request->email,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        } catch (\Throwable $th) {
            return response()->json( [ 'status' => false, 'message' => $th->getMessage() ], 422);
        }

        return response()->json(['status' => true, 'message' => 'Email guardado' ]);
    }

    public function messenger(Request $request)
    {
    
        $socialMessages = new SocialMessages();

        $conversations = $socialMessages->getConversations();

        dd($conversations);
        
        // $urltoken = "https://graph.facebook.com/oauth/access_token?client_id=1135220454605588&client_secret=85d5e99eca4a924916356a1e4cce4dee&grant_type=client_credentials&scope=pages_show_list";

        // $auth = Http::get($urltoken);

        // $auth = $auth->collect();

        

        // $urlaccount = "https://graph.facebook.com/1135220454605588/accounts?access_token=".$auth['access_token'];

        // $account = Http::get($urlaccount);

        // dd($account->body());
        
        // dd($auth);
        // $accesToken = "EAAQIehvwQxQBOz0ZBiyrMlny8HxTs43Kt4v5jk3ZA9Un71ruNsU9AUXgD6GiRqeQbi2M9nsVgfr2vC2ZCtAdJC6UZCDX5oPzULy6xtaG34LH2LYIeJwjYPH0O7xSrdTl7lOSyxueCqx1mM2oILYbC9UOf5v0RpBQ2TRuWS9rvoK5IM5iHhxR2NugVDyKnZAOQUtJlDpFkKbE9ZBEUUXSuo";
        
        // $url = "https://graph.facebook.com/v21.0/me/conversations?access_token=".$accesToken; //TODAS LAS CONVERSACIONES
        // $url = "https://graph.facebook.com/v17.0/1135220454605588/conversations?access_token=".$auth['access_token'];

        // $messages = Http::get($url); 

        // dd($messages->collect());

        // $urlMessage = "https://graph.facebook.com/v21.0/t_10231717396494502?fields=messages&access_token=".$accesToken;

        // $message = Http::get($urlMessage); // UNA SOLA CONVERSACION

        // dd($message->collect());

        // $URLmE = "https://graph.facebook.com/v21.0/m_-ZZS2yRxZhgxWJAXYTQlq48XvQE0W5KZt2yhhzJ67aXFSrw1q2McHlXjHHSG9s_bfTZHoyeu6O5FpETgjIY9Qw?fields=id,created_time,from,to,message&access_token=".$accesToken;

        // $emessage = Http::get($URLmE); //UN MENSAJE

        // dd($emessage->collect());
    }

    public function landing($id)
    {
        return response()->json(Landing::with(['imagenes','campos'])->where('id',$id)->first());
    }

    public function landingsend(Request $request)
    {
        \Log::info($request->all());

        return response()->json(['status', true]);
    }
}
