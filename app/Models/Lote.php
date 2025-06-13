<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;

    protected $fillable = [
        'width',
        'height',
        'm2',
        'sector_id',
        'lote_id',
        'ubication',
        'lote_type_id',
        'lote_status_id',
        'owner_id',
        'frente',
        'contrafrente',
        'lado_uno',
        'lado_dos',
        'is_facturable'
    ];
    protected $with = ['sector','loteStatus','loteType'];

    public function loteStatus()
    {
        return $this->belongsTo(loteStatus::class);
    }

    public function loteType()
    {
        return $this->belongsTo(loteType::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function getNombre()
    {
        return $this->sector->name . $this->lote_id;
    }



}
