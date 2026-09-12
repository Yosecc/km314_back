<?php

namespace App\Services;

use App\Models\FcmDevice;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebaseCloudMessaging
{
    /**
     * @return int Cantidad de dispositivos que Firebase aceptó.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        return $user->fcmDevices()
            ->get()
            ->sum(fn (FcmDevice $device): int => $this->sendToDevice($device, $title, $body, $data) ? 1 : 0);
    }

    public function sendToDevice(FcmDevice $device, string $title, string $body, array $data = []): bool
    {
        try {
            $response = $this->client()->post(
                sprintf(
                    'https://fcm.googleapis.com/v1/projects/%s/messages:send',
                    config('firebase.project_id'),
                ),
                [
                    'message' => [
                        'token' => $device->token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => collect($data)
                            ->map(fn ($value) => (string) $value)
                            ->all(),
                        'android' => ['priority' => 'high'],
                        'webpush' => [
                            'headers' => ['Urgency' => 'high'],
                        ],
                    ],
                ],
            );

            if ($response->successful()) {
                return true;
            }

            if (in_array($response->status(), [400, 404], true)) {
                $device->delete();
            }

            Log::warning('No se pudo enviar una notificación FCM.', [
                'device_id' => $device->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Error al enviar una notificación FCM.', [
                'device_id' => $device->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    protected function client(): PendingRequest
    {
        return Http::acceptJson()->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        return Cache::remember('firebase.fcm.access-token', now()->addMinutes(50), function (): string {
            $credentialsPath = config('firebase.credentials');

            if (! is_string($credentialsPath) || ! is_file($credentialsPath)) {
                throw new RuntimeException('No se encontró la cuenta de servicio de Firebase.');
            }

            $credentials = json_decode((string) file_get_contents($credentialsPath), true);
            if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new RuntimeException('La cuenta de servicio de Firebase no es válida.');
            }

            $issuedAt = now()->timestamp;
            $assertion = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
                . '.'
                . $this->base64UrlEncode(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $issuedAt,
                    'exp' => $issuedAt + 3600,
                ]));

            if (! openssl_sign($assertion, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('No se pudo firmar la credencial de Firebase.');
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion . '.' . $this->base64UrlEncode($signature),
            ]);

            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('No se pudo obtener el token de Firebase.');
            }

            return $response->json('access_token');
        });
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
