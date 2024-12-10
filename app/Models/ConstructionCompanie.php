<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionCompanie extends Model
{
    use HasFactory;

    protected $fillable = ['name','phone','lote_id'];

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function empleados()
    {
        return $this->hasMany(Employee::class,'model_origen_id')
                    ->where('model_origen','ConstructionCompanie')
                    ;
    }

}
