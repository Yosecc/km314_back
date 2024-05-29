<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAutos extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'marca', 'patente', 'modelo', 'color','user_id'];

}
