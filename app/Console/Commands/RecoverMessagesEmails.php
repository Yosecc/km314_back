<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\EmailService;

class RecoverMessagesEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recover-messages-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera los mensajes del email configurado en IMAP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new EmailService();
        $messages = $service->getInboxEmails();

        Cache::put('messagesMail', $messages, now()->addMinutes(35));
        \Log::info('menssages email recuperados');
    }
}
