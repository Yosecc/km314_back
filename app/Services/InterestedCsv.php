<?php

namespace App\Services;

use App\Models\Interested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InterestedCsv
{
    private const COLUMNS = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'dni' => 'nullable|integer|min:0',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:30',
        'address' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'state' => 'nullable|string|max:255',
        'zip_code' => 'nullable|string|max:255',
        'country' => 'nullable|string|max:255',
        'interested_origins_id' => 'required|integer|exists:interested_origins,id',
        'interested_type_id' => 'nullable|integer|exists:interested_types,id',
        'interested_level_id' => 'nullable|integer|exists:interested_levels,id',
        'interested_status_id' => 'nullable|integer|exists:interested_statuses,id',
        'assigned_user_id' => 'nullable|integer|exists:users,id',
        'lote_id' => 'nullable|integer|exists:lotes,id',
        'propertie_id' => 'nullable|integer|exists:properties,id',
        'observations' => 'nullable|string|max:255',
    ];

    public function export($stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        $columns = array_keys(self::COLUMNS);
        fputcsv($stream, $columns, ';', '"', '');

        foreach (Interested::query()->select(['id', ...$columns])->lazyById() as $interested) {
            $row = array_map(function (string $column) use ($interested): string {
                $value = (string) $interested->getAttribute($column);

                // Prevent spreadsheet formulas while preserving the original value on import.
                return $this->needsEscape($value) ? "'".$value : $value;
            }, $columns);
            fputcsv($stream, $row, ';', '"', '');
        }
    }

    public function import($stream, bool $canAssign): array
    {
        $headerLine = fgets($stream);
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', rtrim($headerLine ?: '', "\r\n"));
        $delimiter = str_contains($headerLine, ';') ? ';' : ',';
        $header = str_getcsv($headerLine, $delimiter, '"', '');
        $columns = array_keys(self::COLUMNS);

        if (count($header) !== count($columns) || array_diff($columns, $header)) {
            throw ValidationException::withMessages(['csv' => 'El encabezado no coincide con Exportar CSV. Conservá los nombres de todas las columnas.']);
        }

        return DB::transaction(function () use ($stream, $header, $delimiter, $canAssign) {
            $created = $skipped = 0;
            $rowNumber = 1;

            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                $rowNumber++;
                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($header) || ! mb_check_encoding($row, 'UTF-8')) {
                    $this->invalidRow($rowNumber, 'Revisá la cantidad de columnas y la codificación UTF-8.');
                }

                $data = array_map(function (?string $value): ?string {
                    $value ??= '';
                    if (str_starts_with($value, "'") && $this->needsEscape(substr($value, 1))) {
                        $value = substr($value, 1);
                    }

                    return trim($value) === '' ? null : $value;
                }, array_combine($header, $row));

                if ($data['email'] !== null) {
                    $data['email'] = trim($data['email']);
                    if (Interested::withTrashed()->where('email', $data['email'])->exists()) {
                        $skipped++;

                        continue;
                    }
                }

                if ($data['assigned_user_id'] !== null && ! $canAssign) {
                    $this->invalidRow($rowNumber, 'Necesitás permiso para asignar responsables o dejar esa columna vacía.');
                }

                $validator = Validator::make($data, self::COLUMNS);
                if ($validator->fails()) {
                    $this->invalidRow($rowNumber, $validator->errors()->first());
                }

                Interested::create($validator->validated());
                $created++;
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    private function needsEscape(string $value): bool
    {
        return preg_match('/^(?:[\'\t\r\n]|[\x00-\x20]*[=+@-])/', $value) === 1;
    }

    private function invalidRow(int $row, string $message): never
    {
        throw ValidationException::withMessages(['csv' => "Fila {$row}: {$message}"]);
    }
}
