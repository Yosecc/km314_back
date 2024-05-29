<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OwnerAutos extends Model
{
    use HasFactory;
    protected $fillable = ['owner_id', 'marca', 'patente', 'modelo', 'color','user_id'];
    

}
