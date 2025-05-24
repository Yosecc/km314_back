<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InvoiceService;

class InvoiceObserver
{
    public function created(Invoice $invoice)
    {
        // Actualiza el estado de cuenta al crear una factura
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
    public function updated(Invoice $invoice)
    {
        // Actualiza el estado de cuenta si cambia el total
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
    public function deleted(Invoice $invoice)
    {
        // Actualiza el estado de cuenta si se elimina la factura
        app(InvoiceService::class)->updateAccountStatus($invoice->owner_id);
    }
}
