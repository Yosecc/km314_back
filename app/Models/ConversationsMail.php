<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\EmailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversationsMail extends Model
{
    use Sushi;

    public $timestamps = false;

    protected $fillable = ['id','subject','from','date','body','leido'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $schema = [
        'id' => 'string',
        'subject' => 'string',
        'from' => 'string',
        'date' => 'string',
        'body' => 'string',
        'leido' => 'boolean'
    ];

    public function getRows()
    {
        $messages = Cache::get('messagesMail');

        if(!isset($messages)){
            return [];
        }
        return $messages->toArray();
    }
}
