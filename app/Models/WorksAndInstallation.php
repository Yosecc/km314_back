<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorksAndInstallation extends Model
{
    use HasFactory;

    protected $fillable = ['name','status','color','amount'];

}
