<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Employee extends Model
{
    use HasFactory,SoftDeletes;

    protected $hidden = ['created_at','updated_at'];
    protected $fillable = ['work_id','dni','first_name','last_name','phone','user_id','model_origen','model_origen_id','fecha_vencimiento_seguro','owner_id'];

    public function work()
    {
        return $this->belongsTo(Works::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function autos()
    {
        return $this->hasMany(Auto::class,'model_id')->where('model','Employee');
    }

    public function trabajos()
    {
        return $this->hasMany(Trabajos::class,'trabajo_id');
    }

    public function activitiePeople()
    {
        return $this->hasOne(ActivitiesPeople::class,'model_id')->where('model','Employee')->latest();
    }

    public function isVencidoSeguro()
    {
        if(!$this->fecha_vencimiento_seguro){
            return false;
        }
        return Carbon::parse($this->fecha_vencimiento_seguro) < now() ? true : false;
    }

    public function getFormularios()
    {
        return $this->owner->formControls
            ->where('status', 'Authorized')
            ->filter(function ($formControl) {
                // dd($formControl);
                return $formControl ? $formControl->isDayRange() : false;
            })
            ->values();
    }

    public function isFormularios()
    {
        return count($this->getFormularios()) ? true : false;
    }

    public function formControlPeople($dni): FormControlPeople|null
    {
        $data = $this->getFormularios()->filter(function ($formControl) use ($dni) {
            return $formControl->peoples->contains('dni', $dni);
        })->first();

        if(!$data){
            return null;
        }
        return $data->peoples->where('dni', $dni)->first();
    }

    public function vencidosFile()
    {
        if (!$this->files || $this->files->isEmpty()) {
            return null;
        }

        $files = $this->files
            ->filter(function ($file) {
                return Carbon::parse($file->fecha_vencimiento)->isPast();
            })
            ->pluck('name')
            ->toArray();

        return !empty($files) ? $files : null;
    }

    public function horarios()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function files()
    {
        return $this->hasMany(EmployeeFile::class);
    }

    public function validaHorarios()
    {
        Carbon::setLocale('es');

        // Obtener el día de la semana actual y la hora actual
        $currentDayOfWeek = ucfirst(str_replace(
            ['á', 'é', 'í', 'ó', 'ú'],
            ['a', 'e', 'i', 'o', 'u'],
            Carbon::now()->isoFormat('dddd')
        )); // Ejemplo: "Lunes"
        $currentTime = Carbon::now()->format('H:i:s'); // Ejemplo: "14:30:00"

        if($this->horarios && !$this->horarios->count()){
            return [
                'status' => true,
                'mensaje' => "No tiene horarios configurados."
            ];
        }

        // Iterar sobre la colección de horarios
        foreach ($this->horarios as $horario) {
            // Verificar si el día de la semana coincide
            if ($horario->day_of_week === $currentDayOfWeek) {
                // Verificar si la hora actual está dentro del rango de horario
                if ($currentTime >= $horario->start_time && $currentTime <= $horario->end_time) {
                    return [
                        'status' => true,
                        'mensaje' => 'La hora actual está dentro del horario.'
                    ];
                } elseif ($currentTime < $horario->start_time) {
                    $startTime = Carbon::parse($horario->start_time);
                    $diff = $startTime->diffForHumans(Carbon::now(), true);
                    return [
                        'status' => false,
                        'mensaje' => "El acceso estará disponible en $diff."
                    ];
                } elseif ($currentTime > $horario->end_time) {
                    return [
                        'status' => false,
                        'mensaje' => "El acceso ya no está disponible hasta mañana."
                    ];
                }
            }
        }

        return [
            'status' => false,
            'mensaje' => "Hoy no tiene acceso."
        ];
    }
}
