<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Construction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [ 'construction_type_id','construction_companie_id','construction_status_id','lote_id','owner_id','width','height','m2','observations','deleted_at' ];

    public function constructionStatus()
    {
        return $this->belongsTo(ConstructionStatus::class);
    }

    public function constructionType()
    {
        return $this->belongsTo(ConstructionType::class);
    }

    public function constructionCompanie()
    {
        return $this->belongsTo(ConstructionCompanie::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}
