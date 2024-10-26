<?php

namespace App\Livewire;

use Livewire\Component;

class MessagesMail extends Component
{
    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    public function render()
    {
        return view('livewire.messages-mail');
    }
}
