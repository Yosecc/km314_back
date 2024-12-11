<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormControl extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id','access_type','income_type','tipo_trabajo','is_moroso', 'lote_ids','start_date_range', 'start_time_range', 'end_date_range', 'end_time_range', 'status', 'category', 'authorized_user_id','denied_user_id','user_id','date_unilimited','observations','construction_companie_id'];

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

        $fechaStart = Carbon::parse($this->start_date_range);
        $horaStart = Carbon::parse($this->start_time_range)->format('H');
        $minutoStart = Carbon::parse($this->start_time_range)->format('i');
        $fechaStart->setTime($horaStart, $minutoStart);

        $fechaend = Carbon::parse($this->end_date_range);
        $horaend = Carbon::parse($this->end_time_range)->format('H');
        $minutoend = Carbon::parse($this->end_time_range)->format('i');
        $fechaend->setTime($horaend, $minutoend);

        return [
            'start' => $fechaStart->translatedFormat('l, F d, Y h:i A'),
            'end' =>  $fechaend->translatedFormat('l, F d, Y h:i A'),
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

    public function isDayRange()
    {

        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range;
        // Concatenar las horas a las fechas si están presentes

        $start_date_range .= ' ' . ($this->start_time_range ?  $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');


        // dd( $start_date_range);
        // Convertir las fechas de cadena a objetos Carbon
        $start = Carbon::createFromFormat('Y-m-d H:i',  $start_date_range);
        $end = Carbon::createFromFormat('Y-m-d H:i', $end_date_range);

        // Obtener la fecha actual
        $currentDate = Carbon::now();

        // Verificar si la fecha actual está dentro del rango
        return $currentDate->between($start, $end);
    }

    public function getRangeDate()
    {
        $start_date_range = $this->start_date_range;
        $end_date_range = $this->end_date_range;
        // Concatenar las horas a las fechas si están presentes

        $start_date_range .= ' ' . ($this->start_time_range ?  $this->start_time_range : '00:00');
        $end_date_range .= ' ' . ($this->end_time_range ? $this->end_time_range : '00:00');
        return Carbon::createFromFormat('Y-m-d H:i', $start_date_range)->isoFormat('LLL') . ' / ' . Carbon::createFromFormat('Y-m-d H:i', $end_date_range)->isoFormat('LLL');
    }
}
