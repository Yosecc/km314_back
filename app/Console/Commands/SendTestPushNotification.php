<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Console\Command;

class SendTestPushNotification extends Command
{
    protected $signature = 'push:test {user : ID o email del destinatario}';

    protected $description = 'Envía una notificación de prueba de Firebase a los dispositivos del usuario.';

    public function handle(FirebaseCloudMessaging $messaging): int
    {
        $identifier = (string) $this->argument('user');
        $user = User::query()
            ->whereKey($identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $user) {
            $this->error('No encontramos el usuario indicado.');

            return self::FAILURE;
        }

        $devices = $user->fcmDevices()->count();
        if ($devices === 0) {
            $this->warn('Este usuario todavía no tiene dispositivos registrados.');

            return self::FAILURE;
        }

        $sent = $messaging->sendToUser(
            $user,
            'Notificaciones KM314 activas',
            'Este es un mensaje de prueba para confirmar que todo está listo.',
            ['type' => 'test'],
        );

        if ($sent === 0) {
            $this->error('Firebase no aceptó ningún envío. Revisá storage/logs/laravel.log para ver el motivo exacto.');

            return self::FAILURE;
        }

        $this->info("Firebase aceptó {$sent} de {$devices} dispositivo(s).");

        return self::SUCCESS;
    }
}
