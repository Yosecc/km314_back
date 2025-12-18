<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilesRequired extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'required','no_required'];

    protected $casts = [
        'no_required' => 'array',
        'required' => 'array',
    ];
}
