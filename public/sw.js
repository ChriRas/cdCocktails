const CACHE_NAME = 'cdcocktails-v4';

const ASSETS = [
    '/offline.html',
    '/assets/app.css',
    '/assets/gallery.js',
    '/assets/pwa.js',
    '/assets/logo.webp',
    '/vendor/photoswipe/photoswipe.css',
    '/vendor/photoswipe/photoswipe-lightbox.esm.min.js',
    '/vendor/photoswipe/photoswipe.esm.min.js',
    '/manifest.webmanifest'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Nur same-origin behandeln
    if (url.origin !== location.origin) return;

    // Navigations (HTML-Seitenaufrufe): IMMER network-first, fallback offline
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                // erzwingt echten Netzwerk-Fetch (um SW/HTTP Cache zu umgehen)
                return await fetch(req, { cache: 'no-store' });
            } catch (e) {
                return await caches.match('/offline.html');
            }
        })());
        return;
    }

    // Bilder: network-first (damit neue Uploads sofort da sind), fallback cache, sonst offline
    if (url.pathname.startsWith('/image.php')) {
        event.respondWith(
            fetch(req)
                .then((res) => res)
                .catch(async () => (await caches.match(req)) || (await caches.match('/offline.html')))
        );
        return;
    }

    // Assets: cache-first, fallback network, sonst offline
    event.respondWith(
        caches.match(req).then((cached) => {
            if (cached) return cached;
            return fetch(req).catch(() => caches.match('/offline.html'));
        })
    );
});