<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landing extends Model
{
    use HasFactory;

    protected $fillable = ['title','subtitle','btnactioname','btnactiomessage','status'];

    public function imagenes()
    {
        return $this->hasMany(LandingImage::class,'landing_id');
    }

    public function campos()
    {
        return $this->hasMany(LandingCampos::class);
    }
}
