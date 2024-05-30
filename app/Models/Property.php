<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_type_id',
        'owner_id',
        'width',
        'height',
        'm2',
        'identificador'
    ];

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
    
}
