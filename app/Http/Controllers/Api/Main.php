<?php

namespace App\Http\Controllers\Api;

use App\Mail\Contact;
use App\Models\Slider;
use App\Models\Newsletter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
            return response()->json($th->getMessage(), 422);
        }

        return response()->json(['status' => true, 'message' => 'Email guardado' ]);
    }
}
