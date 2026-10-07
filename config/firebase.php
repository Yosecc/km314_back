<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | La cuenta de servicio nunca debe ubicarse dentro de public ni versionarse.
    | En producción se puede configurar una ruta absoluta con
    | FIREBASE_CREDENTIALS. Por defecto se toma desde storage/app/firebase.
    |
    */
    'credentials' => env(
        'FIREBASE_CREDENTIALS',
        storage_path('app/firebase/service-account.json'),
    ),

    'project_id' => env('FIREBASE_PROJECT_ID', 'km314-f3774'),

    /* Configuración pública utilizada únicamente por el cliente web de FCM. */
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_WEB_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_WEB_APP_ID'),
        'vapid_key' => env('FIREBASE_WEB_VAPID_KEY'),
    ],
];
