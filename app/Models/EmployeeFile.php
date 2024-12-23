<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFile extends Model
{
    use HasFactory;

    protected $fillable = [ 'name', 'file', 'fecha_vencimiento', 'employee_id' ];

}
