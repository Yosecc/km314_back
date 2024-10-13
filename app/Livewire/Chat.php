<?php

namespace App\Livewire;

use Livewire\Component;
use App\Http\Controllers\SocialMessages;

class Chat extends Component
{

    public $conversation;

    public $messages;
    
    public $newMessage;

    public $userId;

    protected $listeners = ['messageSent' => 'updateMessages'];

    public $socialMessages;

    public $urlNext = '';

    public function mount($record)
    {
        $this->conversation = $record;

        $socialMessages = new SocialMessages();
        $this->userId = $socialMessages->account['id'];
        $conversacion = $socialMessages->getConversation($this->conversation->id);

        $this->messages = $conversacion['mensajes'];
        $this->urlNext = $conversacion['url_next'];
        // dd($this->userId,$this->messages);
    }

    public function pageNext(){
        $socialMessages = new SocialMessages();
        $conversacion = $socialMessages->nextPage($this->urlNext);
        
        dd($conversacion['mensajes'],$this->messages);
        $this->messages->merge($conversacion['mensajes']);
        $this->urlNext = $conversacion['url_next'];
    }

    public function updateMessages()
    {
        $socialMessages = new SocialMessages();
        $this->messages = $socialMessages->getConversation($this->conversation->id);
        
    }

    public function sendMessage()
    {
        
        $socialMessages = new SocialMessages();

        $this->messages->prepend(
            collect([
                "id" => uniqid(),
                "created_time" => "2024-10-07T00:01:36+0000",
                "from" => [
                    "name" => $this->conversation['to_name'],
                    "email" => "@facebook.com",
                    "id" => $this->conversation['to_id']
                ],
                "to" => [
                    "data" => [
                        [
                            "name" => $this->conversation['from_name'],
                            "email" => "@facebook.com",
                            "id" => $this->conversation['id']
                        ]
                    ]
                ],
                "message" => $this->newMessage
            ]));

        $this->dispatch('chat-updated'); 
        
        $socialMessages->sendMessage([
                'from_id' => $this->conversation->from_id,
                'message' => $this->newMessage
        ]);

        $this->newMessage = ''; 

        // $this->updateMessages();

    }

    public function render()
    {
        return view('livewire.chat');
    }
}
