<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\InvoiceService;

class InvoiceItemObserver
{
    public function created(InvoiceItem $item)
    {
        // Al crear un item, actualizar el total de la factura y el estado de cuenta
        $invoice = $item->invoice;
        $invoice->total = $invoice->items()->sum('amount');
        $invoice->save();
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
    public function updated(InvoiceItem $item)
    {
        $invoice = $item->invoice;
        $invoice->total = $invoice->items()->sum('amount');
        $invoice->save();
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
    public function deleted(InvoiceItem $item)
    {
        $invoice = $item->invoice;
        $invoice->total = $invoice->items()->sum('amount');
        $invoice->save();
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
}
