const CACHE = 'ceviche-flow-shell-v2';
const SHELL = ['/offline.html', '/manifest.webmanifest'];

self.addEventListener('install', (event) => event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL))));
self.addEventListener('activate', (event) => event.waitUntil(
    caches.keys()
        .then((keys) => Promise.all(keys.filter((key) => key.startsWith('ceviche-flow-shell-') && key !== CACHE).map((key) => caches.delete(key))))
        .then(() => self.clients.claim())
));
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    if (event.request.mode === 'navigate') {
        const url = new URL(event.request.url);
        const isOfflineOrderPage = /^\/orders\/(create|new)\//.test(url.pathname);

        event.respondWith(fetch(event.request).then((response) => {
            if (isOfflineOrderPage && response.ok) {
                caches.open(CACHE).then((cache) => cache.put(event.request, response.clone()));
            }

            return response;
        }).catch(async () => {
            if (isOfflineOrderPage) {
                const cachedPage = await caches.match(event.request);
                if (cachedPage) return cachedPage;
            }

            return caches.match('/offline.html');
        }));
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin || (!url.pathname.startsWith('/build/') && !url.pathname.startsWith('/storage/'))) return;

    event.respondWith(caches.match(event.request).then(async (cached) => {
        if (cached) return cached;

        const response = await fetch(event.request);
        if (response.ok) caches.open(CACHE).then((cache) => cache.put(event.request, response.clone()));
        return response;
    }));
});
