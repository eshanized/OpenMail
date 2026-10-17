'use strict';

const CACHE_NAME = 'openmail-static-v1';
const PRECACHE_URLS = ['/manifest.webmanifest', '/icons/openmail.svg'];
const STATIC_PREFIXES = ['/build/assets/', '/icons/'];
const STATIC_PATHS = new Set(['/manifest.webmanifest']);

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(names => Promise.all(
                names
                    .filter(name => name.startsWith('openmail-static-') && name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Never cache navigations, mailbox responses, search results, auth, or Livewire requests.
    // Only immutable build assets and the minimal PWA metadata are cacheable.
    const isStaticAsset = STATIC_PREFIXES.some(prefix => url.pathname.startsWith(prefix))
        || STATIC_PATHS.has(url.pathname);
    if (!isStaticAsset || request.mode === 'navigate') return;

    event.respondWith(
        caches.match(request).then(async cached => {
            if (cached) return cached;

            try {
                const response = await fetch(request);
                if (response.ok && response.type === 'basic') {
                    const cache = await caches.open(CACHE_NAME);
                    await cache.put(request, response.clone());
                }
                return response;
            } catch {
                return new Response('', {
                    status: 503,
                    statusText: 'Static asset unavailable offline',
                    headers: { 'Cache-Control': 'no-store' }
                });
            }
        })
    );
});
