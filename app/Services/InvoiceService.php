<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\Lote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\AccountStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Genera facturas mensuales para todos los propietarios y lotes.
     */
    public function generateMonthlyInvoices($period = null)
    {
        $period = $period ?: Carbon::now()->startOfMonth();
        $owners = Owner::with('lotes')->get();

        foreach ($owners as $owner) {
            foreach ($owner->lotes as $lote) {
                DB::transaction(function () use ($owner, $lote, $period) {
                    $invoice = Invoice::create([
                        'owner_id' => $owner->id,
                        'lote_id' => $lote->id,
                        'period' => $period,
                        'total' => 0,
                        'status' => 'pendiente',
                    ]);

                    $total = 0;

                    // Ejemplo de ítems fijos
                    $fixedItems = [
                        ['description' => 'Expensas fijas', 'amount' => 10000, 'is_fixed' => true],
                        // Agrega más ítems fijos si es necesario
                    ];

                    foreach ($fixedItems as $item) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'description' => $item['description'],
                            'amount' => $item['amount'],
                            'is_fixed' => $item['is_fixed'],
                        ]);
                        $total += $item['amount'];
                    }

                    // Aquí puedes agregar lógica para ítems variables
                    // ...

                    $invoice->update(['total' => $total]);

                    // Actualizar estado de cuenta
                    $this->updateAccountStatus($owner->id, $total, 0, $invoice->id, null);
                });
            }
        }
    }

    /**
     * Registra un pago y lo aplica a facturas pendientes.
     */
    public function registerPayment($ownerId, $amount, $paymentDate, $method = null, $notes = null)
    {
        DB::transaction(function () use ($ownerId, $amount, $paymentDate, $method, $notes) {
            $payment = Payment::create([
                'owner_id' => $ownerId,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'method' => $method,
                'notes' => $notes,
            ]);

            // Aplicar pago a facturas pendientes
            $pendingInvoices = Invoice::where('owner_id', $ownerId)
                ->where('status', 'pendiente')
                ->orderBy('period')
                ->get();

            $remaining = $amount;
            foreach ($pendingInvoices as $invoice) {
                if ($remaining <= 0) break;
                $toPay = min($invoice->total, $remaining);
                // Registrar en tabla pivote
                DB::table('invoice_payment')->insert([
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'amount' => $toPay,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Actualizar estado de la factura si se paga completa
                if ($toPay == $invoice->total) {
                    $invoice->update(['status' => 'pagada']);
                }
                $remaining -= $toPay;
            }

            // Actualizar estado de cuenta
            $this->updateAccountStatus($ownerId, 0, $amount, null, $payment->id);
        });
    }

    /**
     * Actualiza el estado de cuenta del propietario.
     */
    public function updateAccountStatus($ownerId, $invoiced, $paid, $lastInvoiceId = null, $lastPaymentId = null)
    {
        $account = AccountStatus::firstOrCreate(['owner_id' => $ownerId]);
        $account->total_invoiced += $invoiced;
        $account->total_paid += $paid;
        $account->balance = $account->total_paid - $account->total_invoiced;
        if ($lastInvoiceId) $account->last_invoice_id = $lastInvoiceId;
        if ($lastPaymentId) $account->last_payment_id = $lastPaymentId;
        $account->save();
    }
}
