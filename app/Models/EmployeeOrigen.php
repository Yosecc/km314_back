<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOrigen extends Model
{
    use HasFactory;
    protected $fillable = ['model','model_id','employee_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
