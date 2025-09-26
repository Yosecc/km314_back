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
            $this->error("El archivo {$file} no existe.");
            return 1;
        }

        $this->info("Iniciando importación desde: {$file}");
        $this->info("Owner ID: {$ownerId}, Lote ID: {$loteId}");

        // DEBUG: Verificar que los modelos existan
        $owner = \App\Models\Owner::find($ownerId);
        if (!$owner) {
            $this->error("❌ Owner con ID {$ownerId} no existe!");
            return 1;
        }
        $this->info("✅ Owner encontrado: {$owner->first_name} {$owner->last_name}");

        $lote = \App\Models\Lote::find($loteId);
        if (!$lote) {
            $this->error("❌ Lote con ID {$loteId} no existe!");
            return 1;
        }
        $this->info("✅ Lote encontrado: {$lote->lote_id}");

        try {
            // Leer el archivo con encoding correcto
            $content = file_get_contents($file);
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            $lines = explode("\n", $content);
            
            $headerFound = false;
            $rowCount = 0;
            $invoiceCount = 0;
            $paymentCount = 0;
            $skippedCount = 0;

            $this->info("📄 Total de líneas en archivo: " . count($lines));

            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line))) continue;
                
                $row = str_getcsv($line, ';');
                
                // DEBUG: Mostrar las primeras filas para verificar estructura
                if ($lineNumber < 10) {
                    $this->info("Línea {$lineNumber}: " . json_encode($row));
                }
                
                // Buscar la línea del header (puede tener caracteres especiales)
                if (!$headerFound) {
                    if (count($row) >= 8 && 
                        (strpos($row[0], 'Fecha') !== false || strpos(strtolower($row[0]), 'fecha') !== false)) {
                        $headerFound = true;
                        $this->info("✅ Header encontrado en línea " . ($lineNumber + 1));
                        continue;
                    }
                    continue;
                }

                // Saltar filas que no tienen la estructura correcta o son separadores
                if (count($row) < 8 || 
                    empty(trim($row[0])) || 
                    $this->isSeperatorRow($row[0]) ||
                    $this->isSeperatorRow($row[1])) {
                    $this->comment("Saltando línea {$lineNumber}: " . json_encode($row));
                    continue;
                }

                $rowCount++;

                // Parsear los datos
                $fechaRaw = trim($row[0]);
                $detalle = trim($row[1], '" ');
                $periodo = trim($row[2]);
                $valor = $this->parseImporte($row[3]);
                $punitorios = $this->parseImporte($row[4]);
                $pagos = $this->parseImporte($row[6]);

                // DEBUG: Mostrar datos parseados
                $this->info("🔍 Datos parseados fila {$rowCount}:");
                $this->info("  Fecha: '{$fechaRaw}' | Detalle: '{$detalle}' | Período: '{$periodo}'");
                $this->info("  Valor: {$valor} | Punitorios: {$punitorios} | Pagos: {$pagos}");

                // Validar y corregir fecha
                $fecha = $this->parseDate($fechaRaw);
                if (!$fecha) {
                    $this->warn("⚠️  Fila {$rowCount}: Fecha inválida '{$fechaRaw}', saltando...");
                    $skippedCount++;
                    continue;
                }

                // Validar que la fecha sea razonable (no futura más de 1 año)
                if ($fecha->gt(now()->addYear())) {
                    $this->warn("⚠️  Fila {$rowCount}: Fecha sospechosa '{$fechaRaw}' -> {$fecha->format('Y-m-d')}, saltando...");
                    $skippedCount++;
                    continue;
                }

                $this->line("📋 Procesando fila {$rowCount}: {$detalle} - {$fecha->format('Y-m-d')}");

                // DEBUG: Verificar tipo de registro
                $isInvoiceType = $this->isInvoice($detalle);
                $isPaymentType = $this->isPayment($detalle);
                
                $this->info("  Es factura: " . ($isInvoiceType ? 'SÍ' : 'NO'));
                $this->info("  Es pago: " . ($isPaymentType ? 'SÍ' : 'NO'));

                // Determinar si es factura o pago
                if ($isInvoiceType) {
                    $this->info("💰 Intentando crear factura...");
                    if ($this->createInvoice($fecha, $detalle, $periodo, $valor, $punitorios, $ownerId, $loteId)) {
                        $invoiceCount++;
                        $this->info("✅ Factura creada exitosamente!");
                    } else {
                        $this->error("❌ Error creando factura");
                    }
                } elseif ($isPaymentType) {
                    $this->info("💳 Intentando crear pago...");
                    if ($this->createPayment($fecha, $detalle, $periodo, $pagos ?: $valor, $ownerId, $loteId)) {
                        $paymentCount++;
                        $this->info("✅ Pago creado exitosamente!");
                    } else {
                        $this->error("❌ Error creando pago");
                    }
                } else {
                    $this->warn("⚠️  Fila {$rowCount}: Tipo no determinado para '{$detalle}' - saltando");
                    $skippedCount++;
                }
            }

            $this->info("📊 Importación completada:");
            $this->info("- Filas procesadas: {$rowCount}");
            $this->info("- Facturas creadas: {$invoiceCount}");
            $this->info("- Pagos creados: {$paymentCount}");
            $this->info("- Filas saltadas: {$skippedCount}");

            // DEBUG: Verificar registros creados en BD
            $totalInvoices = \App\Models\Invoice::where('owner_id', $ownerId)->count();
            $totalPayments = \App\Models\Payment::where('owner_id', $ownerId)->count();
            $this->info("📈 Totales en BD para Owner {$ownerId}:");
            $this->info("- Total facturas en BD: {$totalInvoices}");
            $this->info("- Total pagos en BD: {$totalPayments}");

        } catch (\Exception $e) {
            $this->error("💥 Error durante la importación: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            \Log::error("Error en importación CSV: " . $e->getMessage() . " - " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function isSeperatorRow($value)
    {
        $value = strtolower(trim($value));
        $separators = [
            'año', 'a�o', 'nuevo propietario', 'propietario', 
            ';;;;;;;;', '-------', '=======',
            '', 'total', 'subtotal'
        ];
        
        foreach ($separators as $sep) {
            if (strpos($value, $sep) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function parseDate($dateString)
    {
        try {
            $dateString = trim($dateString);
            
            // Corregir años obviamente incorrectos
            $dateString = preg_replace('/(\d{1,2}\/\d{1,2}\/)2028/', '${1}2024', $dateString);
            $dateString = preg_replace('/(\d{1,2}\/\d{1,2}\/)22020/', '${1}2020', $dateString);
            
            // Intentar diferentes formatos de fecha
            $formats = ['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y'];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateString);
                    if ($date) {
                        // Validación adicional: no fechas futuras irrazonables
                        if ($date->year < 2000 || $date->year > now()->year + 1) {
                            continue;
                        }
                        return $date;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Último intento con parseo automático
            $date = Carbon::parse($dateString);
            if ($date && $date->year >= 2000 && $date->year <= now()->year + 1) {
                return $date;
            }

        } catch (\Exception $e) {
            \Log::error("Error parseando fecha '{$dateString}': " . $e->getMessage());
        }
        
        return null;
    }

    private function isInvoice($detalle)
    {
        $detalle = strtolower($detalle);
        return strpos($detalle, 'cuota') !== false || 
               strpos($detalle, 'mant') !== false ||
               strpos($detalle, 'extraordinaria') !== false ||
               strpos($detalle, 'mantenimiento') !== false;
    }

    private function isPayment($detalle)
    {
        $detalle = strtolower($detalle);
        return strpos($detalle, 'pago') !== false || 
               strpos($detalle, 'cobrado') !== false ||
               strpos($detalle, 'pg.') !== false ||
               strpos($detalle, 'banco') !== false ||
               strpos($detalle, 'pg ') !== false;
    }

    private function createInvoice($fecha, $detalle, $periodo, $valor, $punitorios, $ownerId, $loteId)
    {
        try {
            $this->info("🔧 DEBUG createInvoice:");
            $this->info("  Fecha: {$fecha->format('Y-m-d')} | Detalle: {$detalle}");
            $this->info("  Período: {$periodo} | Valor: {$valor} | Punitorios: {$punitorios}");
            $this->info("  Owner ID: {$ownerId} | Lote ID: {$loteId}");

            // Evitar duplicados por período
            $existingInvoice = Invoice::where('owner_id', $ownerId)
                ->where('lote_id', $loteId)
                ->where('period', $periodo ?: $fecha->format('Ym'))
                ->first();
                
            if ($existingInvoice) {
                $this->warn("⚠️  Factura ya existe para período {$periodo}, saltando...");
                return false;
            }

            $this->info("💾 Creando factura en BD...");
            
            // Crear la factura
            $invoice = Invoice::create([
                'owner_id' => $ownerId,
                'lote_id' => $loteId,
                'invoice_date' => $fecha,
                'due_date' => $fecha->copy()->addMonth(),
                'period' => $periodo ?: $fecha->format('Ym'),
                'status' => 'sent',
                'total_amount' => $valor + $punitorios,
                'notes' => $detalle,
            ]);

            $this->info("✅ Factura creada con ID: {$invoice->id}");

            // Agregar ítem principal
            if ($valor > 0) {
                $this->info("💾 Creando ítem principal...");
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'expense_concept_id' => 1,
                    'description' => $detalle,
                    'quantity' => 1,
                    'unit_price' => $valor,
                    'total' => $valor,
                ]);
                $this->info("✅ Ítem principal creado con ID: {$item->id}");
            }

            // Agregar ítem de punitorios si existe
            if ($punitorios > 0) {
                $this->info("💾 Creando ítem punitorios...");
                $punitorio = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'expense_concept_id' => 2,
                    'description' => 'Punitorios',
                    'quantity' => 1,
                    'unit_price' => $punitorios,
                    'total' => $punitorios,
                ]);
                $this->info("✅ Ítem punitorios creado con ID: {$punitorio->id}");
            }

            $this->info("✅ Factura completa creada: ID {$invoice->id}, Período {$periodo}, Total: $" . number_format($valor + $punitorios, 2));
            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error creando factura: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            \Log::error("Error creando factura para período {$periodo}: " . $e->getMessage() . " - " . $e->getTraceAsString());
            return false;
        }
    }

    private function createPayment($fecha, $detalle, $periodo, $importe, $ownerId, $loteId)
    {
        try {
            $this->info("🔧 DEBUG createPayment:");
            $this->info("  Fecha: {$fecha->format('Y-m-d')} | Detalle: {$detalle}");
            $this->info("  Período: {$periodo} | Importe: {$importe} | Owner ID: {$ownerId} | Lote ID: {$loteId}");

            // Si el importe es 0 o negativo, no crear pago
            if ($importe <= 0) {
                $this->warn("⚠️  Importe {$importe} <= 0, saltando pago...");
                return false;
            }

            $this->info("💾 Creando pago en BD...");
            
            $payment = Payment::create([
                'owner_id' => $ownerId,
                'lote_id' => $loteId,
                'payment_date' => $fecha,
                'amount' => $importe,
                'payment_method' => 'bank_transfer',
                'reference' => $periodo ?: $fecha->format('Ym'),
                'notes' => $detalle,
                'status' => 'confirmed',
            ]);

            $this->info("✅ Pago creado con ID: {$payment->id}");
            $this->info("✅ Pago completo: ID {$payment->id}, Fecha {$fecha->format('Y-m-d')}, Importe: $" . number_format($importe, 2));
            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error creando pago: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            \Log::error("Error creando pago para fecha {$fecha}: " . $e->getMessage() . " - " . $e->getTraceAsString());
            return false;
        }
    }

    public static function parseImporte($valor)
    {
        if (empty($valor) || trim($valor) === '' || trim($valor) === '-') {
            return 0;
        }

        // Limpiar el valor
        $valor = trim($valor);
        $valor = str_replace('$', '', $valor);
        $valor = str_replace(' ', '', $valor);
        $valor = str_replace('---', '0', $valor);
        $valor = str_replace('--------', '0', $valor);
        $valor = str_replace('----', '0', $valor);

        // Manejar casos especiales del CSV
        if (strpos($valor, '-') !== false && $valor !== '-') {
            // Si hay un guión pero no es solo guión, podría ser negativo
            $valor = str_replace('-', '', $valor);
        }

        // Si contiene coma como separador decimal
        if (strpos($valor, ',') !== false) {
            // Si hay punto Y coma, la coma es decimal
            if (strpos($valor, '.') !== false && strpos($valor, ',') > strpos($valor, '.')) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
            // Si solo hay coma, podría ser miles o decimal
            else {
                // Si la coma está en los últimos 3 dígitos, es decimal
                if (strlen($valor) - strpos($valor, ',') <= 3) {
                    $valor = str_replace(',', '.', $valor);
                } else {
                    $valor = str_replace(',', '', $valor);
                }
            }
        }

        $valor = preg_replace('/[^0-9.-]/', '', $valor);
        return (float) $valor;
    }
}
