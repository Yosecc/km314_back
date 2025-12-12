<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlDateRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_control_id',
        'start_date_range',
        'start_time_range',
        'end_date_range',
        'end_time_range',
        'date_unilimited'
    ];

    protected $casts = [
        'date_unilimited' => 'boolean',
    ];

    public function formControl()
    {
        return $this->belongsTo(FormControl::class);
    }

    /**
     * Verifica si la fecha actual está dentro de este rango
     */
    public function isInRange(): bool
    {
        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range ?? Carbon::now()->addMonth()->format('Y-m-d');

        $start_date_range .= ' ' . ($this->start_time_range ? $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');

        $start_date_range = substr($start_date_range, 0, 16);
        $start = Carbon::createFromFormat('Y-m-d H:i', $start_date_range);
        $end_date_range = substr($end_date_range, 0, 16);
        $end = Carbon::createFromFormat('Y-m-d H:i', $end_date_range);

        $currentDate = Carbon::now();

        return $currentDate->between($start, $end);
    }

    /**
     * Retorna el rango de fechas formateado
     */
    public function getFormattedRange(): string
    {
        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range;

        $start_date_range .= ' ' . ($this->start_time_range ? $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');

        $start_date_range = substr($start_date_range, 0, 16);
        $end_date_range = substr($end_date_range, 0, 16);

        return Carbon::createFromFormat('Y-m-d H:i', $start_date_range)->isoFormat('LLL') . ' / ' . Carbon::createFromFormat('Y-m-d H:i', $end_date_range)->isoFormat('LLL');
    }

    /**
     * Combina fecha y hora en un objeto Carbon
     */
    private function combineDateTime(?string $date, ?string $time): ?Carbon
    {
        if (!$date || !$time) {
            return null;
        }

        try {
            $fecha = Carbon::parse($date);
            $hora = Carbon::parse($time)->format('H:i');
            return $fecha->setTimeFromTimeString($hora);
        } catch (\Exception $e) {
            return null;
        }
    }
}
