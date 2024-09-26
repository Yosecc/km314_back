<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FormControl extends Model
{
    use HasFactory;

    protected $fillable = ['owner_id','access_type','income_type','is_moroso', 'lote_ids','start_date_range', 'start_time_range', 'end_date_range', 'end_time_range', 'status', 'category', 'authorized_user_id','denied_user_id','user_id','date_unilimited','observations'];

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

    public function statusComputed():string
    {
        $status = $this->status;
        
        $today = Carbon::now();

        $fechaStart = Carbon::parse($this->start_date_range);
        // Extraer la hora del campo start_time_range
        $horaStart = Carbon::parse($this->start_time_range)->format('H');
        $minutoStart = Carbon::parse($this->start_time_range)->format('i');
        
        // Asignar la hora y minuto a la fecha
        $fechaStart->setTime($horaStart, $minutoStart);

        if(!$this->date_unilimited){
           
            if ($fechaStart->lessThan($today)){ #Si ya paso la fecha
                if($status == 'Pending'){
                    $status = 'Vencido';
                }
            }

            $fechaEnd = Carbon::parse($this->end_date_range);
            $horaEnd = Carbon::parse($this->end_time_range)->format('H');
            $minutoEnd = Carbon::parse($this->end_time_range)->format('i');
            $fechaEnd->setTime($horaEnd, $minutoEnd);

            if ($fechaEnd->lessThan($today)){
                if($status == 'Pending' || $status == 'Vencido'){
                    $status = 'Expirado';
                }
            }

        }else{
            // dd($today,$fechaStart);
            if ($fechaStart->lessThan($today)){ #Si ya paso la fecha
                if($status == 'Pending'){
                    $status = 'Vencido';
                }
            }
        }

        return $status;
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

    public function lotes()
    {
        return $this->hasMany(FormControlLote::class);
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
