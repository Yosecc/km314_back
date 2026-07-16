<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaEnElBarrio extends Model
{
    use HasFactory;

    protected $table = 'personas_en_el_barrio';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function getLoteAttribute($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded) ? implode(', ', $decoded) : (string) $decoded;
        }

        $cleanValue = trim((string) $value, "[]\" \t\n\r\0\x0B");
        $cleanValue = str_replace(['\",\"', '","', '\"'], [', ', ', ', ''], $cleanValue);
        $unicodeDecoded = json_decode('"' . str_replace('"', '\"', $cleanValue) . '"');

        return json_last_error() === JSON_ERROR_NONE ? $unicodeDecoded : $cleanValue;
    }
}
