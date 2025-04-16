<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageProfileAssignments extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'type', 'message_id', 'user_id'];
}
