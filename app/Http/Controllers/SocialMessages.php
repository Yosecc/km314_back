<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
class SocialMessages extends Controller
{
    private $urlBase = "https://graph.facebook.com/";

    private $version = "v21.0";

    private $urlconversations = "/me/conversations";
    
    private $urlaccounts = "/me/accounts";

    private $urlmessages = "/me/messages";

    private $token = "";

    private $pageAccessToken;

    private $queryConversations;

    public $account;//PAGE ID

    public $conversations = [];

    public $redirectUri = '';

    public function __construct()
    {
        // Construir la query token con la propiedad de instancia $token
       
        $this->redirectUri = config('app.url') . '/auth/facebook/callback';

        if(Cache::store('file')->has('access_token')){

            $this->token = Cache::store('file')->get('access_token');
        }else{
            dd('No hay token');
        }

        $this->getAccounts(); 
        
        $this->pageAccessToken = $this->account['access_token'];
        $this->queryConversations = "?fields=participants,messages{id,message}&access_token=" . $this->pageAccessToken ;
        // $this->conversations = Cache::has('conversations') ? Cache::get('conversations') : [];
        // $this->auth();
       
        // dd($r);
    }

    public function debugToken()
    {
        // $url = $this->urlBase . "/oauth/access_token?client_id=".config('providers.facebook.app_id')."&redirect_uri='".$this->redirectUri."'&client_secret=".config('providers.facebook.app_secret')."&code=".$code;

    }

    public function setTokenApp($code)
    {
        
        // $url = $this->urlBase . $this->version . "/oauth/access_token?client_id=".config('providers.facebook.app_id')."&grant_type=client_credentials&client_secret=".config('providers.facebook.app_secret')."&code=".$code;

        // $response = Http::get($url); 
        // $response = $response->json();

        // dd($response);

        // $this->token = $response['access_token'];

         $u = "https://graph.facebook.com/v21.0/me?fields=id,name&access_token=".$this->token;

         $response = Http::get($u); 
         $response = $response->json();
        
         dd($this->token, $u ,$response);
    }

    public function setTokenPage()
    {
        
        // $url = $this->urlBase . $this->version . "/oauth/access_token?client_id=".config('providers.facebook.app_id')."&redirect_uri=".$this->redirectUri."&client_secret=".config('providers.facebook.app_secret')."&code=".$code;

        // $response = Http::get($url); 
        // $response = $response->json();
        // $this->token = $response['access_token'];
    }

    public function auth()
    {
        // $url = $this->urlBase . $this->version . $this->urlaccounts . "?access_token=" . $this->token;

        // $url = "https://graph.facebook.com/oauth/access_token?client_id=1135220454605588&client_secret=85d5e99eca4a924916356a1e4cce4dee&grant_type=client_credentials";
        // $url = "https://graph.facebook.com/oauth?access_token=1135220454605588|85d5e99eca4a924916356a1e4cce4dee";
        
        // $response = Http::get($url); 

        // $response = $response->json();

        // $url = "https://graph.facebook.com/accounts?access_token=1135220454605588|".$response['access_token'];
        // $response = Http::get($url); 
        // $response = $response->json();
        // dd($response);


        // $appsecret_proof= hash_hmac('sha256', $response['access_token'].'|'.time(), "85d5e99eca4a924916356a1e4cce4dee"); 
        // dd($response, $appsecret_proof);
        //84858e85298a19ed947a3d316ecbc552
        // $urlPage = "https://graph.facebook.com/me?access_token=1135220454605588|84858e85298a19ed947a3d316ecbc552" ;
        // $urlPage = "https://graph.facebook.com/1135220454605588/accounts?access_token=".$response['access_token'] ;

        // $response = Http::get($urlPage); 

        // $response = $response->json();

        // dd($response);

    }

    public function getAccounts()
    {
        $url = $this->urlBase . $this->version . $this->urlaccounts . "?access_token=" . $this->token;
        
        $response = Http::get($url); 

        $response = $response->collect();

        if(isset($response['error'])){
            dd('Error',$response['error']);
            return;
        }

        $this->account = $response['data'][0];
// dd($this->account);
        return $response;

    }

    public function getConversations()
    {
        try {
            $url = $this->urlBase . $this->version . $this->urlconversations . $this->queryConversations;

            $response = Http::get($url); 

            $response = $response->collect();

            if(isset($response['error'])){
                dd('Error',$response['error']);
                return;
            }

            $this->conversations = $response['data'];

            Cache::put('conversations', $this->conversations);

            return $this->conversations;
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function getConversation($conversation_id)
    {
        try {
            $url = $this->urlBase . $this->version . "/" . $conversation_id . "?fields=messages{id,message}&access_token=" . $this->pageAccessToken;
       
            $response = Http::get($url); 

            $response = $response->collect();
            
            if(isset($response['error'])){
                dd('Error',$response['error']);
                return;
            }

            $messages = collect($response['messages']['data']);
            $messages = $messages->map(function($message){
                $url = $this->urlBase . $this->version . "/" . $message['id'] . "?fields=id,created_time,from,to,message&access_token=" . $this->pageAccessToken;
                return $url;
            });

            $mensajes = Http::pool(fn (Pool $pool) => $messages->map(function($c) use ($pool){
                return $pool->get($c);
            }));

            $mensajes = collect($mensajes)->map(fn($response) => $response->collect());

            return $mensajes;
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function sendMessage($data)
    {
        try {

            $url = $this->urlBase . $this->version . $this->urlmessages . "?access_token=" . $this->pageAccessToken;

            $response = Http::post($url,[
                'recipient' => "{id:".$data['from_id']."}",
                'message' => "{'text':'" . $data['message'] . "'}",
                'messaging_type' => 'RESPONSE'
            ]); 

            if(isset($response['error'])){
                dd('Error',$response['error']);
                return;
            }

        } catch (\Throwable $th) {
            //throw $th;

            dd($th->getMessage());
        }
        
    }
}

