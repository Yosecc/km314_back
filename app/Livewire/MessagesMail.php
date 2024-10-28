<?php

namespace App\Livewire;

use Livewire\Component;
use App\Mail\SendMessageMail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\EmailService;
use Filament\Notifications\Notification;

class MessagesMail extends Component
{
    public $record;

    public $messages;

    public $newMessage;

    // public $service;

    public function mount($record)
    {

        $this->record = $record;

        $service = new EmailService();
        $this->messages = $service->getHilo($this->record['id']);

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
