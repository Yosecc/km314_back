<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Message;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Cache;

class EmailService extends Controller
{
    private $client;

    public function __construct()
    {
        // Conectar al cliente
        $this->client = Client::account('default');
        $this->client->connect();
    }

    private function perpareMessages( $messages)
    {
        try {
            $messages = collect($messages);

            $messages = $messages->map(function($message){
                $attribute = $message->getAttributes();
                // dd([$message,$attribute]);

                $from = isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : '';
                $subject = isset($message->getSubject()[0]) ? $message->getSubject()[0] : '';
                $date = isset($message->getDate()[0]) ? $message->getDate()[0]->format('Y-m-d H:m:s') : '';
                return [
                    'id' => $attribute['uid'],
                    'subject' => $subject,
                    'from' => $from,
                    'date' => $date,
                    'body' => $message->getHTMLBody() != "" ? $message->getHTMLBody() : $message->getTextBody(),
                    'leido' => $message->getFlags()->contains('Seen'),
                    'references' => isset($attribute['references']) ? $attribute['references'][0] : '',
                    'message_id' => isset($attribute['message_id']) ? $attribute['message_id'][0] : '',
                ];
            })->sortByDesc('date')->values();

        } catch (\Throwable $th) {
            \Log::inf($th->getMessage());
        }

        return $messages;
    }

    public function getInboxEmails()
    {
        // Obtener la bandeja de entrada
        $folder = $this->client->getFolder('INBOX');
		//dd($folder);
        // Obtener todos los mensajes
        $messages = $folder->messages()->setFetchOrderDesc()->all()->limit($limit = 100)->get();

        $this->client->disconnect();

        return $this->perpareMessages($messages);
    }
    public function getInboxAssigned()
    {
        // Obtener la bandeja de entrada
        $folder = $this->client->getFolderByPath('INBOX.Assigned');
        // Obtener los mensajes
        $messages = $folder->messages()->setFetchOrderDesc()->all()->limit($limit = 100)->get();

        $this->client->disconnect();

        return $this->perpareMessages($messages);
    }

    public function getHilo($message_id, $isAssigned)
    {
        // $client = Client::account('default');
        // $client->connect();

        if($isAssigned){
            $folder = $this->client->getFolderByPath('INBOX.Assigned');
        }else{
            $folder = $this->client->getFolder('INBOX');
        }

        // Recuperar un mensaje
        $message = $folder->query()->getMessageByUid($message_id);

        // dd($message->getAttributes());


        $messages = $message->thread($this->client->getFolderByPath('INBOX.Sent'));

        // dd( $messages);
        $this->client->disconnect();
        // dd($this->perpareMessages($messages));

        return $this->perpareMessages($messages);

    }

    public function newMessage($data)
    {

        $mail = new PHPMailer(true);

        try {
            // Definir configuración básica
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->setFrom(config('mail.mailers.smtp.username'), config('mail.mailers.smtp.host'));
            $mail->addAddress($data['record']['from']);
            $mail->isHTML(true);
            $mail->Subject = $data['record']['subject'];
            $mail->Body = $data['message'];

            // Generar el mensaje completo en formato MIME
            $rawMessage = "From: ".config('mail.mailers.smtp.username')." <" . config('mail.mailers.smtp.username') . ">\r\n";
            $rawMessage .= "To: " . $data['record']['from'] . "\r\n";
            $rawMessage .= "Subject: " . $data['record']['subject'] . "\r\n";
            $rawMessage .= "MIME-Version: 1.0\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";

            $rawMessage .= "Content-Transfer-Encoding: base64\r\n";

            $messageIdToReply = isset($data['record']['message_id']) && $data['record']['message_id'] != '' ? $data['record']['message_id'] : null;

            if ($messageIdToReply) {
                $rawMessage .= "In-Reply-To: <$messageIdToReply>\r\n";
                $rawMessage .= "References: <$messageIdToReply>\r\n";
            }

            $rawMessage .= "Message-ID: <" . uniqid() . "@" . config('mail.mailers.smtp.host') . ">\r\n";

            $rawMessage .= "\r\n"; // Espacio entre encabezados y cuerpo
            $rawMessage .= chunk_split(base64_encode($data['message']));

            // 3. Guardar en "Enviados" usando IMAP
            $imapHost = '{'.config('imap.accounts.default.host').':'.config('imap.accounts.default.port').'/imap/'.config('imap.accounts.default.encryption').'}';
            // dd($imapHost);
            $imapUsername = config('imap.accounts.default.username');
            $imapPassword = config('imap.accounts.default.password');
            $sentFolder = 'INBOX.Sent';

            // Abrir conexión IMAP
            $imapStream = imap_open($imapHost, $imapUsername, $imapPassword);

            if ($imapStream) {
                $appendResult = imap_append($imapStream, $imapHost . $sentFolder, $rawMessage, "\\Seen");
                imap_close($imapStream);
                // Verificamos si se guardó correctamente
                \Log::info($appendResult ? 'Mensaje guardado en Enviados con encabezados y contenido completos' : 'Error al guardar en Enviados');
            } else {
                \Log::info('No se pudo conectar a IMAP para guardar en Enviados');
            }
        } catch (Exception $e) {
            \Log::info("Error al construir el mensaje o guardarlo en Enviados: {$e->getMessage()}");
        }
    }

    public function moveToTrash($message_id)
    {
        $folder = $this->client->getFolder('INBOX');
        $message = $folder->query()->getMessageByUid($message_id);

        // Mover el mensaje a la papelera
        $message->move('INBOX.Trash');

        // Desconectar el cliente
        $this->client->disconnect();
    }
    public function moveToAssigned($message_id)
    {
        $folder = $this->client->getFolder('INBOX');
        $message = $folder->query()->getMessageByUid($message_id);

        // Mover el mensaje a la papelera
        $message->move('INBOX.Assigned');

        // $messagesAssigned = $this->getInboxAssigned();
        // Almacenar los mensajes en caché por 35 minutos
        // Cache::put('messagesAssigned', $messagesAssigned, now()->addMinutes(35));

        // Desconectar el cliente
        $this->client->disconnect();
    }
    public function markRead($message_id, $isAssigned = false)
    {
        if($isAssigned){
            $folder = $this->client->getFolderByPath('INBOX.Assigned');
        }else{
            $folder = $this->client->getFolder('INBOX');
        }

        $message = $folder->query()->getMessageByUid($message_id);

        $message->setFlag('Seen');

        $this->client->disconnect();
    }


    // $status = $client->isConnected();

    // $folders = $client->getFolders($hierarchical = true);

    // dd($message->thread($client->getFolder('INBOX')));

}
