const CACHE_NAME = 'sniperpos-v2';
const OFFLINE_URL = '/offline.html';
const APP_SHELL = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/simple-pos-icon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            )
        )
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(event.request.url);

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    // Authenticated HTML pages are always network-only. If the device is offline,
    // show the static fallback instead of serving cached sales or business data.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    const isSafeStaticAsset =
        requestUrl.pathname === '/manifest.webmanifest' ||
        requestUrl.pathname.startsWith('/icons/') ||
        requestUrl.pathname.startsWith('/build/');

    if (! isSafeStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                if (! response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                const responseClone = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));

                return response;
            });
        })
    );
});
