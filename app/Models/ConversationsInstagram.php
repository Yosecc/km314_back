<?php

namespace App\Models;

use App\Http\Controllers\SocialMessages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class ConversationsInstagram extends Model
{
    use Sushi;

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

    public function getRows()
    {
        $socialMessages = new SocialMessages();
        // $this->pageId = $socialMessages->account['id'];

        $conversations = $socialMessages->getConversationsInstagram();

        // Obtener las conversaciones del caché
        // $conversations = Cache::get('conversations', []);
        // Mapea los datos del caché para que se ajusten a la estructura esperada
        return collect($conversations)->map(function ($conversation) {
            return [
                'id' => $conversation['id'],
                'from_name' => $conversation['participants']['data'][0]['name'] ?? '',
                'from_id' => $conversation['participants']['data'][0]['id'] ?? '',
                'to_name' => $conversation['participants']['data'][1]['name'] ?? '',
                'to_id' => $conversation['participants']['data'][1]['id'] ?? '',
                'last_message_id' => $conversation['messages']['data'][0]['id'] ?? '',
                'last_message_created_time' => $conversation['messages']['data'][0]['created_time'] ?? '',
            ];
        })->toArray(); // Convertimos la colección en array
    }

}
