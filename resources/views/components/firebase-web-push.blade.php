@php
    $firebase = config('firebase.web');
    $isConfigured = filled($firebase['api_key'] ?? null) && filled($firebase['vapid_key'] ?? null);
    $firebaseConfigJson = json_encode([
        'apiKey' => $firebase['api_key'] ?? null,
        'authDomain' => $firebase['auth_domain'] ?? null,
        'projectId' => $firebase['project_id'] ?? null,
        'storageBucket' => $firebase['storage_bucket'] ?? null,
        'messagingSenderId' => $firebase['messaging_sender_id'] ?? null,
        'appId' => $firebase['app_id'] ?? null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $vapidKeyJson = json_encode($firebase['vapid_key'] ?? null);
    $registerDeviceUrlJson = json_encode(route('push.devices.web'));
@endphp

@if ($isConfigured)
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js';
        import { getMessaging, getToken, onMessage } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging.js';

        const firebaseConfig = {!! $firebaseConfigJson !!};
        const vapidKey = {!! $vapidKeyJson !!};
        const registerDeviceUrl = {!! $registerDeviceUrlJson !!};

        async function registerBrowserForPush() {
            if (!('Notification' in window) || !('serviceWorker' in navigator)) return;

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            const messaging = getMessaging(initializeApp(firebaseConfig));
            const token = await getToken(messaging, {
                vapidKey,
                serviceWorkerRegistration: registration,
            });

            if (!token) return;

            await fetch(registerDeviceUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({
                    token,
                    platform: 'web',
                    device_name: navigator.userAgent.slice(0, 255),
                }),
            });

            onMessage(messaging, (payload) => {
                const notification = payload.notification;
                if (!notification || Notification.permission !== 'granted') return;

                new Notification(notification.title ?? 'KM314', {
                    body: notification.body ?? '',
                    icon: '/images/logo-blue.png',
                });
            });
        }

        // Solo se solicita una vez por navegador. Si el usuario ya decidió,
        // se registra silenciosamente en los siguientes ingresos.
        registerBrowserForPush().catch(() => {});
    </script>
@endif
