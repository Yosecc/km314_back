<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected $listeners = ['refreshInvoiceTotal' => 'updateTotal'];

    public function updateTotal()
    {
        // Recalcula el total sumando los ítems relacionados y actualiza el campo en el formulario
        if ($this->record) {
            $total = $this->record->items()->sum('amount');
            $this->form->fill(['total' => $total]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
