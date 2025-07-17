<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\InvoiceService;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        // Llama al servicio para aplicar el pago y actualizar estados
        app(InvoiceService::class)->applyPayment($payment);
    }
}
