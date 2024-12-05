<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [ 'name','date_incident','description','status','user_id'];

    public function notes(){
        return $this->hasMany(IncidentNote::class);
    }

}
