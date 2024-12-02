<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormControlFile extends Model
{
    use HasFactory;

    protected $fillable = [ 'form_control_id' ,  'user_id', 'file', 'description' ];

    protected $hidden = ['id','form_control_id','user_id','created_at','updated_at'];
}
