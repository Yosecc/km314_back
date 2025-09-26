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

        $facturas = [];
        $errores = [];
        $linea = 0;
        $headers = null;
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linea++;
            
            // Saltar filas vacías o con información del propietario
            if (empty($row[0]) || count($row) < 6) {
                continue;
            }
            
            // Detectar la fila de encabezados
            if (stripos($row[0], 'fecha') !== false && stripos($row[1], 'detalle') !== false) {
                $headers = $row;
                continue;
            }
            
            // Saltar filas separadoras como "AÑO 2020"
            if (stripos($row[1], 'año') !== false || empty($row[0]) || strlen(trim($row[0])) < 5) {
                continue;
            }
            
            try {
                // Mapear las columnas según el formato del CSV
                $fecha = trim($row[0] ?? '');
                $desc = trim($row[1] ?? '');
                $periodo = trim($row[2] ?? '');
                $valor = trim($row[3] ?? '');
                $punitorios = trim($row[4] ?? '');
                $pagos = trim($row[6] ?? ''); // Columna "Pagos"
                
                // Conversión robusta de importes
                $amount = self::parseImporte($valor);
                $punitorio = self::parseImporte($punitorios);
                $pago = self::parseImporte($pagos);

                // Ítem de factura (más flexible: busca 'cuota' y 'mant' en cualquier orden)
                if (preg_match('/cuota.*mant|mant.*cuota/i', $desc)) {
                    // Usar la fecha del CSV para el período
                    $fechaCarbon = $this->parseDate($fecha);
                    if (!$fechaCarbon) {
                        $this->warn("Fecha inválida en línea {$linea}: {$fecha}");
                        continue;
                    }
                    
                    $periodoKey = $periodo ?: $fechaCarbon->format('Ym');
                    
                    // Buscar o crear factura para ese periodo
                    $factura = Invoice::firstOrCreate([
                        'owner_id' => $ownerId,
                        'lote_id' => $loteId,
                        'period' => $periodoKey,
                    ], [
                        'due_date' => $fechaCarbon->copy()->addDays(10),
                        'total' => 0,
                        'status' => 'pendiente',
                        'invoice_date' => $fechaCarbon,
                    ]);
                    
                    // Ítem principal
                    if ($amount > 0) {
                        InvoiceItem::create([
                            'invoice_id' => $factura->id,
                            'description' => $desc,
                            'amount' => $amount,
                            'is_fixed' => true,
                            'expense_concept_id' => 4,
                        ]);
                    }
                    
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
                    
                    $facturas[$periodoKey] = $factura;
                }
                // Pagos
                elseif ((stripos($desc, 'pago') !== false || stripos($desc, 'cobrado') !== false || stripos($desc, 'pg.') !== false) && $pago > 0) {
                    $fechaCarbon = $this->parseDate($fecha);
                    if (!$fechaCarbon) {
                        $this->warn("Fecha inválida para pago en línea {$linea}: {$fecha}");
                        continue;
                    }
                    
                    Payment::create([
                        'owner_id' => $ownerId,
                        'lote_id' => $loteId,
                        'amount' => $pago,
                        'payment_date' => $fechaCarbon,
                        'method' => 'Migración CSV',
                        'notes' => $desc,
                    ]);
                } else {
                    // Log de fila ignorada
                    \Log::info('[ImportAccountingCsv] Fila ignorada', [
                        'linea' => $linea,
                        'desc' => $desc,
                        'fecha' => $fecha,
                        'amount' => $amount,
                        'punitorio' => $punitorio,
                        'pago' => $pago,
                        'row' => $row
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
     * Parsea una fecha en varios formatos posibles
     */
    private function parseDate($dateString)
    {
        $dateString = trim($dateString);
        if (empty($dateString)) {
            return null;
        }
        
        // Formatos posibles
        $formats = [
            'd/m/Y',    // 25/01/2024
            'j/n/Y',    // 2/10/2018
            'd/m/y',    // 25/01/24
            'j/n/y',    // 2/10/18
        ];
        
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }

    /**
     * Convierte un string de importe en float, soportando formatos con coma o punto decimal.
     */
    public static function parseImporte($valor)
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === null || $valor === '-' || strpos($valor, '----') !== false) {
            return 0.0;
        }
        
        // Remover símbolos de moneda y espacios
        $valor = str_replace(['$', ' '], '', $valor);
        
        // Si tiene punto y coma: $ 2,600.00 => quitar comas (miles)
        if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
            $valor = str_replace(',', '', $valor);
        } 
        // Si solo tiene coma: 2600,00 => reemplazar por punto
        elseif (strpos($valor, ',') !== false) {
            $valor = str_replace(',', '.', $valor);
        }
        
        return floatval($valor);
    }
}
