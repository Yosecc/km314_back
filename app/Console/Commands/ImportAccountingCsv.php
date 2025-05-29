<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Carbon\Carbon;

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

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("No se pudo abrir el archivo: $file");
            return 1;
        }
        $headers = fgetcsv($handle, 0, ';');
        $facturas = [];
        $errores = [];
        $linea = 1;
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linea++;
            try {
                $row = array_combine($headers, $row);
                $fecha = trim($row['created_at'] ?? '');
                $desc = trim($row['description'] ?? '');
                // Conversión robusta de importes
                $amount = self::parseImporte($row['amount'] ?? '0');
                $punitorio = self::parseImporte($row['punitorio'] ?? '0');
                $pago = self::parseImporte($row['pago'] ?? '0');

                // Ítem de factura
                if (stripos($desc, 'cuota mant') !== false || stripos($desc, 'cuota mantenimiento') !== false) {
                    $periodo = \Carbon\Carbon::createFromFormat('d/m/Y', $fecha)->startOfMonth();
                    // Buscar o crear factura para ese periodo
                    $factura = Invoice::firstOrCreate([
                        'owner_id' => $ownerId,
                        'lote_id' => $loteId,
                        'period' => $periodo->toDateString(),
                    ], [
                        'due_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $fecha)->addDays(10),
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
                        if (\Carbon\Carbon::parse($f->period)->lte(\Carbon\Carbon::createFromFormat('d/m/Y', $fecha))) {
                            $factura = $f;
                        }
                    }
                    Payment::create([
                        'owner_id' => $ownerId,
                        'amount' => $pago,
                        'payment_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $fecha),
                        'method' => 'Migración CSV',
                        'notes' => $desc,
                    ]);
                }
            } catch (\Throwable $e) {
                $errores[] = "Línea $linea: " . $e->getMessage();
                \Log::error('[ImportAccountingCsv] Error en línea ' . $linea . ': ' . $e->getMessage(), ['row' => $row ?? null]);
                continue;
            }
        }
        fclose($handle);
        if (count($errores)) {
            $this->error('Errores durante la importación:');
            foreach ($errores as $err) {
                $this->error($err);
            }
        } else {
            $this->info('Importación finalizada sin errores.');
        }
        return 0;
    }

    /**
     * Convierte un string de importe en float, soportando formatos con coma o punto decimal.
     */
    public static function parseImporte($valor)
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === null) return 0.0;
        // Si tiene punto y coma: 144,913.13 => quitar comas (miles)
        if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
            $valor = str_replace(',', '', $valor);
        } else {
            // Si solo tiene coma: 144913,13 => reemplazar por punto
            $valor = str_replace(',', '.', $valor);
        }
        return floatval($valor);
    }
}
