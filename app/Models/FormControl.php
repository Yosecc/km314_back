<?php

namespace App\Models;

use App\Traits\HasQuickAccessCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class FormControl extends Model
{
    use HasFactory, SoftDeletes, HasQuickAccessCode;


    protected $fillable = ['owner_id','access_type','income_type','tipo_trabajo','is_moroso', 'lote_ids','start_date_range', 'start_time_range', 'end_date_range', 'end_time_range', 'status', 'category', 'authorized_user_id','denied_user_id','user_id','date_unilimited','observations','construction_companie_id', 'quick_access_code'];

    protected $casts = [
        'lote_ids' => 'array',
        'access_type' => 'array',
        'income_type' => 'array'
    ];

    public function aprobar()
    {
        if($this->status != 'Authorized'){
            $this->status = 'Authorized';
            $this->authorized_user_id = Auth::user()->id;
            $this->save();
        }

    }

    public function rechazar()
    {
        if($this->status != 'Denied'){
            $this->status = 'Denied';
            $this->denied_user_id = Auth::user()->id;
            $this->save();
        }
    }

    public function statusComputed(): string
    {
        $status = $this->status;
        $today = Carbon::now('America/Argentina/Buenos_Aires');

        // Combinar fecha y hora de inicio
        $fechaStart = $this->combineDateTime($this->start_date_range, $this->start_time_range);

        if (!$this->date_unilimited) {
            // Combinar fecha y hora de fin
            $fechaEnd = $this->combineDateTime($this->end_date_range, $this->end_time_range);

            // Verificar si la fecha de fin ya pasó
            if ($fechaEnd && $fechaEnd->lessThan($today)) {
                if (in_array($status, ['Pending', 'Vencido'])) {
                    return 'Expirado';
                }
            }
        }

        // Verificar si la fecha de inicio ya pasó
        if ($fechaStart && $fechaStart->lessThan($today)) {
            if ($status === 'Pending') {
                return 'Vencido';
            }
        }

        return $status;
    }

    /**
     * Combina una fecha y una hora en un objeto Carbon.
     *
     * @param string|null $date La fecha (en formato `Y-m-d`).
     * @param string|null $time La hora (en formato `H:i:s` o similar).
     * @return Carbon|null Objeto Carbon combinado, o null si no se puede construir.
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
            // Manejar errores si los valores no son válidos
            return null;
        }
    }

    public function isVencido() :bool
    {
        return $this->statusComputed() === 'Vencido' ? true : false;
    }

    public function isActive()
    {
        return $this->statusComputed() == 'Authorized' ? true : false;
    }

    public function isDenied()
    {
        return $this->statusComputed() == 'Denied' ? true : false;
    }

    public function isPending()
    {
        return $this->statusComputed() == 'Pending' ? true : false;
    }

    public function isExpirado()
    {
        return $this->statusComputed() == 'Expirado' ? true : false;
    }

    public function getFechasFormat()
    {
        Carbon::setLocale('es');

        // Si hay dateRanges, toma el primero (o el que corresponda)
        if ($this->dateRanges && $this->dateRanges->count() > 0) {
            $range = $this->dateRanges->first();
            $start = Carbon::parse($range->start_date_range . ' ' . $range->start_time_range);
            $end = Carbon::parse($range->end_date_range . ' ' . $range->end_time_range);
        } else {
            // Fallback a los campos propios (por compatibilidad)
            $start = Carbon::parse($this->start_date_range . ' ' . $this->start_time_range);
            $end = Carbon::parse($this->end_date_range . ' ' . $this->end_time_range);
        }

        return [
            'start' => $start->isoFormat('dddd, D [de] MMMM [de] YYYY hh:mm a'),
            'end' => $end->isoFormat('dddd, D [de] MMMM [de] YYYY hh:mm a'),
            '_start' => $start,
            '_end'=> $end,
        ];

    }

    public function lotes()
    {
        return $this->hasMany(FormControlLote::class);
    }

    public function files()
    {
        return $this->hasMany(FormControlFile::class);
    }

    public function authorizedUser()
    {
        return $this->belongsTo(User::class,'authorized_user_id');
    }

    public function mascotas()
    {
        return $this->hasMany(FormControlMascota::class);
    }

    public function deniedUser()
    {
        return $this->belongsTo(User::class,'denied_user_id');
    }

    public function peoples()
    {
        return $this->hasMany(FormControlPeople::class);
    }

    public function peopleResponsible()
    {
        return $this->hasOne(FormControlPeople::class)->where('is_responsable',true);
    }

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','FormControl');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function dateRanges()
    {
        return $this->hasMany(FormControlDateRange::class);
    }
    

    public function isDayRange()
    {
        // Si tiene rangos de fechas definidos, verificar si alguno es válido
        if ($this->dateRanges()->exists()) {
            foreach ($this->dateRanges as $dateRange) {
                if ($dateRange->isInRange()) {
                    return true;
                }
            }
            return false;
        }

        // Fallback a los campos legacy (para compatibilidad con registros antiguos)
        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range ?? Carbon::now()->addMonth()->format('Y-m-d');

        // Concatenar las horas a las fechas si están presentes
        $start_date_range .= ' ' . ($this->start_time_range ?  $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');

        // Convertir las fechas de cadena a objetos Carbon
        $start_date_range = substr($start_date_range, 0, 16);
        $start = Carbon::createFromFormat('Y-m-d H:i', $start_date_range);
        $end_date_range = substr($end_date_range, 0, 16);
        $end = Carbon::createFromFormat('Y-m-d H:i', $end_date_range);

        // Obtener la fecha actual
        $currentDate = Carbon::now();

        // Verificar si la fecha actual está dentro del rango
        return $currentDate->between($start, $end);
    }

    public function getRangeDate()
    {
        // Si tiene rangos de fechas, devolver todos concatenados
        if ($this->dateRanges()->exists()) {
            return $this->dateRanges->map(function ($dateRange) {
                return $dateRange->getFormattedRange();
            })->implode(' | ');
        }

        // Fallback a los campos legacy
        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range;
        
        $start_date_range .= ' ' . ($this->start_time_range ?  $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');
        
        $start_date_range = substr($start_date_range, 0, 16);
        $end_date_range = substr($end_date_range, 0, 16);
        
        return Carbon::createFromFormat('Y-m-d H:i', $start_date_range)->isoFormat('LLL') . ' / ' . Carbon::createFromFormat('Y-m-d H:i', $end_date_range)->isoFormat('LLL');
    }
}
