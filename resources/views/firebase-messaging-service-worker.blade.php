@php
    $firebaseConfigJson = json_encode([
        'apiKey' => $firebase['api_key'] ?? null,
        'authDomain' => $firebase['auth_domain'] ?? null,
        'projectId' => $firebase['project_id'] ?? null,
        'storageBucket' => $firebase['storage_bucket'] ?? null,
        'messagingSenderId' => $firebase['messaging_sender_id'] ?? null,
        'appId' => $firebase['app_id'] ?? null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
// Este archivo se genera desde variables de entorno: no contiene claves en Git.
import { initializeApp } from '/firebase/firebase-app.js';
import { getMessaging } from '/firebase/firebase-messaging-sw.js';

const app = initializeApp({!! $firebaseConfigJson !!});
getMessaging(app);
