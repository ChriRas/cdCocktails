const CACHE_NAME = 'cdcocktails-v1';

const ASSETS = [
    '/',
    '/assets/app.css',
    '/assets/gallery.js',
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

    // Bilder lieber network-first (damit neue Uploads sofort da sind)
    if (url.pathname.startsWith('/image.php')) {
        event.respondWith(fetch(req).catch(() => caches.match(req)));
        return;
    }

    // Assets: cache-first
    event.respondWith(
        caches.match(req).then(cached => cached || fetch(req))
    );
});