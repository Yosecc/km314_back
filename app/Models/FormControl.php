<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class FormControl extends Model
{
    use HasFactory;

    protected $fillable = ['access_type','income_type','is_moroso', 'lote_ids','start_date_range', 'start_time_range', 'end_date_range', 'end_time_range', 'status', 'category', 'authorized_user_id','user_id','date_unilimited','observations'];

    protected $casts = [
        'lote_ids' => 'array',
        'access_type' => 'array',
        'income_type' => 'array'
    ];

    public function lotes()
    {
        return $this->hasMany(FormControlLote::class);
    }

    public function authorizedUser()
    {
        return $this->belongsTo(User::class,'authorized_user_id');
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
