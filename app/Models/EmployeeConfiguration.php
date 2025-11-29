<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['type','employee_id','data'];
}
