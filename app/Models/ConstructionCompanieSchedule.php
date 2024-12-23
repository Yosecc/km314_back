<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionCompanieSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['construction_companie_id', 'day_of_week', 'start_time', 'end_time'];

    public function construnctionCompanie()
    {
        return $this->belongsTo(ConstructionCompanie::class);
    }
}
