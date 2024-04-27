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
        'owner_id'
    ];
    public function loteStatus()
    {
        return $this->belongsTo(LoteStatus::class);
    }

    public function loteType()
    {
        return $this->belongsTo(LoteType::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

}
