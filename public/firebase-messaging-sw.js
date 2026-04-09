// Firebase Messaging Service Worker
// This file is served from /firebase-messaging-sw.js and MUST be at root of scope

importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');

// Config is injected at runtime from /api/crm/firebase-config
// We cache it in the SW after first fetch
let app;
let messaging;

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

// Listen for messages from the main page (sends Firebase config)
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        try {
            if (!app) {
                app = firebase.initializeApp(event.data.config);
                messaging = firebase.messaging(app);
            }
        } catch (e) {
            // Already initialized
        }
    }
});

// Background message handler - shows native OS notification
self.addEventListener('push', event => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { notification: { title: 'New Notification', body: event.data.text() } };
    }

    const notification = payload.notification || {};
    const data         = payload.data || {};

    const title   = notification.title || data.title || 'Hotel CRM';
    const body    = notification.body  || data.body  || '';
    const iconUrl = notification.icon  || data.icon_url || '/icon-192.png';
    const url     = notification.click_action || data.click_url || '/';

    event.waitUntil(
        self.registration.showNotification(title, {
            body,
            icon: iconUrl,
            badge: '/icon-72.png',
            data: { url },
            requireInteraction: false,
            tag: 'crm-push',
        })
    );
});

// Handle notification click — open or focus the tab
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
            for (const client of list) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
