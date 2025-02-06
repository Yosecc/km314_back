<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestNote extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id','user_id','description'];

    protected $with = ['user'];

    protected $hidden = ['updated_at','user_id','service_request_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
