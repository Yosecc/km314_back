<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;

class EmailService extends Controller
{
    
    public function getInboxEmails()
    {
        // Conectar al cliente
        $client = Client::account('default');
        $client->connect();

        // $status = $client->isConnected();

        // $folders = $client->getFolders($hierarchical = true);

        // Obtener la bandeja de entrada
        $folder = $client->getFolder('INBOX');
        $messages = $folder->messages()->all()->get();

        $messages = collect($messages);

        $messages = $messages->map(function($message){
            $attribute = $message->getAttributes();            
            $from = isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : '';
            $subject = isset($message->getSubject()[0]) ? $message->getSubject()[0] : '';
            $date = isset($message->getDate()[0]) ? $message->getDate()[0]->format('Y-m-d H:m:s') : '';
            return [
                'id' => $attribute['uid'],
                'subject' => $subject,
                'from' => $from,
                'date' => $date,
                'body' => $message->getHTMLBody(),
                'leido' => $message->getFlags()->contains('Seen')
            ];
        })->sortByDesc('date')->values();

        return $messages;
    }

}
