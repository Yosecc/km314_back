/* Service worker para las notificaciones web de KM314. */
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.5/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyDRxn9r1trsksMLb0gDaBR2j5RgZXpI6KY',
    authDomain: 'km314-f3774.firebaseapp.com',
    projectId: 'km314-f3774',
    storageBucket: 'km314-f3774.firebasestorage.app',
    messagingSenderId: '738187282879',
    appId: '1:738187282879:web:d97b34e551faeede2ff90a',
});

firebase.messaging();
