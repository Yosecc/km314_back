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

    protected $fillable = ['id','subject','from','date','body','leido','references','message_id'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $schema = [
        'id' => 'string',
        'subject' => 'string',
        'from' => 'string',
        'date' => 'string',
        'body' => 'string',
        'leido' => 'boolean',
        'references' => 'string',
        'message_id' => 'string'
    ];

    public function getRows()
    {

//         $service = new EmailService();
//         $messages = $service->getInboxEmails();
// // dd($messages);

        $messages = Cache::get('messagesMail');

        if(!isset($messages)){
            return [];
        }
        return $messages->toArray();
    }
}
