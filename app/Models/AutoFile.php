<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoFile extends Model
{
    use HasFactory;

    protected $fillable = ['name','file','fecha_vencimiento','auto_id'];
}
