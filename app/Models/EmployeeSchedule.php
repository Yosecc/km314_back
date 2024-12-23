<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'day_of_week', 'start_time', 'end_time'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
