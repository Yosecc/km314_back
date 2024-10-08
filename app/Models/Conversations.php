<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\SocialMessages;

class Conversations extends Model
{
    use Sushi;

    // Deshabilitar las timestamps, ya que no estamos usando una base de datos real
    public $timestamps = false;

    protected $fillable = ['id','from_name','from_id','to_name','to_id','last_message_id','last_message_created_time'];

    public $incrementing = false;

    protected $keyType = 'string';

    public $pageId;

    // Definir las columnas que quieres manejar en memoria
    protected $schema = [
        'id' => 'string',
        'from_name' => 'string',
        'from_id' => 'string',
        'to_name' => 'string',
        'to_id' => 'string',
        'last_message_id' => 'string',
        'last_message_created_time' => 'string',
    ];

    // El método rows() es requerido para devolver los datos que quieres manejar en memoria
    public function getRows()
    {
        $socialMessages = new SocialMessages();
        $this->pageId = $socialMessages->account['id'];

        $conversations = $socialMessages->getConversations();
        
        // Obtener las conversaciones del caché
        // $conversations = Cache::get('conversations', []);

        // Mapea los datos del caché para que se ajusten a la estructura esperada
        return collect($conversations)->map(function ($conversation) {
            return [
                'id' => $conversation['id'],
                'from_name' => $conversation['participants']['data'][0]['name'],
                'from_id' => $conversation['participants']['data'][0]['id'],
                'to_name' => $conversation['participants']['data'][1]['name'],
                'to_id' => $conversation['participants']['data'][1]['id'],
                'last_message_id' => $conversation['messages']['data'][0]['id'],
                'last_message_created_time' => $conversation['messages']['data'][0]['created_time'],
            ];
        })->toArray(); // Convertimos la colección en array
    }

    public function sendMessage($message)
    {
       
        $socialMessages = new SocialMessages();
        
        $socialMessages->sendMessage([
                'from_id' => $this->from_id,
                'message' => $message
        ]);

        
        
    }

    // protected function sushiShouldCache()
    // {
    //     return true;
    // }
}
