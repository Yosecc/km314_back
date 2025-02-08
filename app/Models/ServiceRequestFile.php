<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestFile extends Model
{
    use HasFactory;

    protected $hidden = ['updated_at'];

    protected $fillable = ['service_request_id','user_id','file','description','attachment_file_names'];

	  public function user()
    {
        return $this->belongsTo(User::class);
    }
}
