<?php

namespace App\Http\Livewire;

use Livewire\Component;

class QrModal extends Component
{
    public $record;
    public $entityType;
    public $show = false;

    protected $listeners = ['openQrModal' => 'open'];

    public function open($record, $entityType)
    {
        $this->record = $record;
        $this->entityType = $entityType;
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.qr-modal');
    }
}
