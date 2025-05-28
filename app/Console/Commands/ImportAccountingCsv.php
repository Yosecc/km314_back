<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Carbon\Carbon;
use League\Csv\Reader;

class ImportAccountingCsv extends Command
{
    protected $signature = 'import:accounting-csv {file} {owner_id} {lote_id}';
    protected $description = 'Importa facturas y pagos desde un CSV de administración contable';

    public function handle()
    {
        $file = $this->argument('file');
        $ownerId = $this->argument('owner_id');
        $loteId = $this->argument('lote_id');

        if (!file_exists($file)) {
            $this->error("Archivo no encontrado: $file");
            return 1;
        }

        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0);
        $rows = iterator_to_array($csv->getRecords());

        $facturas = [];
        foreach ($rows as $row) {
            $fecha = trim($row['created_at'] ?? '');
            $desc = trim($row['description'] ?? '');
            $amount = floatval(str_replace([',', '.'], ['.', ''], $row['amount'] ?? '0'));
            $punitorio = floatval(str_replace([',', '.'], ['.', ''], $row['punitorio'] ?? '0'));
            $pago = floatval(str_replace([',', '.'], ['.', ''], $row['pago'] ?? '0'));

            // Ítem de factura
            if (stripos($desc, 'cuota mant') !== false || stripos($desc, 'cuota mantenimiento') !== false) {
                $periodo = Carbon::createFromFormat('d/m/Y', $fecha)->startOfMonth();
                // Buscar o crear factura para ese periodo
                $factura = Invoice::firstOrCreate([
                    'owner_id' => $ownerId,
                    'lote_id' => $loteId,
                    'period' => $periodo->toDateString(),
                ], [
                    'due_date' => Carbon::createFromFormat('d/m/Y', $fecha)->addDays(10),
                    'total' => 0,
                    'status' => 'pendiente',
                ]);
                // Ítem principal
                InvoiceItem::create([
                    'invoice_id' => $factura->id,
                    'description' => $desc,
                    'amount' => $amount,
                    'is_fixed' => true,
                    'expense_concept_id' => 4,
                ]);
                // Ítem punitorio
                if ($punitorio > 0) {
                    InvoiceItem::create([
                        'invoice_id' => $factura->id,
                        'description' => 'Punitorio',
                        'amount' => $punitorio,
                        'is_fixed' => false,
                        'expense_concept_id' => 5,
                    ]);
                }
                $facturas[$periodo->format('Y-m')] = $factura;
            }
            // Pagos
            if (stripos($desc, 'pago') !== false && $pago > 0) {
                // Buscar la factura más cercana anterior o igual a la fecha del pago
                $factura = null;
                foreach ($facturas as $key => $f) {
                    if (Carbon::parse($f->period)->lte(Carbon::createFromFormat('d/m/Y', $fecha))) {
                        $factura = $f;
                    }
                }
                Payment::create([
                    'owner_id' => $ownerId,
                    'amount' => $pago,
                    'payment_date' => Carbon::createFromFormat('d/m/Y', $fecha),
                    'method' => 'Migración CSV',
                    'notes' => $desc,
                ]);
            }
        }
        $this->info('Importación finalizada.');
        return 0;
    }
}
