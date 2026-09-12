@php
    $firebase = config('firebase.web');
    $isConfigured = filled($firebase['api_key'] ?? null) && filled($firebase['vapid_key'] ?? null);
@endphp

@if ($isConfigured)
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js';
        import { getMessaging, getToken } from 'https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging.js';

        const firebaseConfig = @json([
            'apiKey' => $firebase['api_key'],
            'authDomain' => $firebase['auth_domain'],
            'projectId' => $firebase['project_id'],
            'storageBucket' => $firebase['storage_bucket'],
            'messagingSenderId' => $firebase['messaging_sender_id'],
            'appId' => $firebase['app_id'],
        ]);
        const vapidKey = @json($firebase['vapid_key']);

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

            await fetch(@json(route('push.devices.web')), {
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
        }

        // Solo se solicita una vez por navegador. Si el usuario ya decidió,
        // se registra silenciosamente en los siguientes ingresos.
        registerBrowserForPush().catch(() => {});
    </script>
@endif
