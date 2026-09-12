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
        'api_key' => env('FIREBASE_WEB_API_KEY', 'AIzaSyDRxn9r1trsksMLb0gDaBR2j5RgZXpI6KY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN', 'km314-f3774.firebaseapp.com'),
        'project_id' => env('FIREBASE_WEB_PROJECT_ID', 'km314-f3774'),
        'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET', 'km314-f3774.firebasestorage.app'),
        'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID', '738187282879'),
        'app_id' => env('FIREBASE_WEB_APP_ID', '1:738187282879:web:d97b34e551faeede2ff90a'),
        'vapid_key' => env('FIREBASE_WEB_VAPID_KEY'),
    ],
];
