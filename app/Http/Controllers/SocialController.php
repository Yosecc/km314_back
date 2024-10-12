<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $value = $request->session()->all();
        dd($value );
        
        //if (request('error') == 'access_denied') 
            //handle error  

        $accessToken = $this->facebook->handleCallback(); 
        dd($accessToken);
        // return $accessToken;
        //use token to get facebook pages
    }

}
