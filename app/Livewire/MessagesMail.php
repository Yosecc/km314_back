<?php

namespace App\Livewire;

use App\Http\Controllers\EmailService;
use App\Mail\SendMessageMail;
use App\Models\ConversationsMail;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class MessagesMail extends Component
{
    public $record;

    public $messages;

    public $newMessage;

    public $isAssigned;

    // public $service;

    public function mount($record, $isAssigned)
    {

        $this->record = $record;
        $this->isAssigned = $isAssigned;

        $service = new EmailService();
        
        $this->messages = $service->getHilo($this->record['id'], $this->isAssigned);
        
        $service->markRead($this->record['id'], $this->isAssigned);

        $con = ConversationsMail::where('id', $this->record['id'])->first();
        $con->markRead();

    }

    public function sendMessage()
    {
        if($this->newMessage == ''){
            return;
        }

        $data = [
            'message' => $this->newMessage,
            'record' => $this->record,
        ];

        // dd($data);
        try {
            Mail::to($this->record['from'])->send(new SendMessageMail($data));

            $service = new EmailService();
            $service->newMessage($data);

            $this->newMessage = '';

            $this->messages = $service->getHilo($this->record['id']);

            Notification::make()
                ->title('Mensaje enviado')
                ->success()
                ->send();
        } catch (\Throwable $th) {
            Notification::make()
                ->title($th->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.messages-mail');
    }
}
