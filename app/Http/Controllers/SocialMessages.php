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
    
    private $urlaccounts = "/me";

    private $urlmessages = "/me/messages";

    private $token = "";

    private $queryConversations;

    public $account;//PAGE ID

    public $conversations = [];

    public function __construct()
    {
        // Construir la query token con la propiedad de instancia $token
        $this->queryConversations = "?fields=participants,messages{id,message}&access_token=" . $this->token;

        // $this->conversations = Cache::has('conversations') ? Cache::get('conversations') : [];
        $this->auth();
        
        $this->getAccounts(); 
    }

    public function auth()
    {
       

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

        $this->account = $response;

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
            $url = $this->urlBase . $this->version . "/" . $conversation_id . "?fields=messages{id,message}&access_token=" . $this->token;
       
            $response = Http::get($url); 

            $response = $response->collect();
            
            if(isset($response['error'])){
                dd('Error',$response['error']);
                return;
            }

            $messages = collect($response['messages']['data']);
            $messages = $messages->map(function($message){
                $url = $this->urlBase . $this->version . "/" . $message['id'] . "?fields=id,created_time,from,to,message&access_token=" . $this->token;
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

            $url = $this->urlBase . $this->version . $this->urlmessages . "?access_token=" . $this->token;

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

