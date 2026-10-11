const CACHE_NAME = 'scroll-news-static-v1';
const VERSIONED_ASSET_PATH = /^\/assets\/(?:css|js)\/.+\.(?:css|js)$/i;

self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames
      .filter((cacheName) => cacheName.startsWith('scroll-news-static-') && cacheName !== CACHE_NAME)
      .map((cacheName) => caches.delete(cacheName)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const requestUrl = new URL(request.url);
  const version = requestUrl.searchParams.get('v');

  if (request.method !== 'GET'
    || requestUrl.origin !== self.location.origin
    || !VERSIONED_ASSET_PATH.test(requestUrl.pathname)
    || requestUrl.searchParams.size !== 1
    || !/^\d+$/.test(version || '')) {
    return;
  }

  event.respondWith((async () => {
    let cache;
    let cachedResponse;
    try {
      cache = await caches.open(CACHE_NAME);
      cachedResponse = await cache.match(request);
    } catch (_) {
      return fetch(request);
    }

    if (cachedResponse) return cachedResponse;

    const response = await fetch(request);
    const cacheControl = (response.headers.get('Cache-Control') || '').toLowerCase();
    if (response.ok
      && response.type === 'basic'
      && !cacheControl.includes('no-store')
      && !cacheControl.includes('private')) {
      try {
        await cache.put(request, response.clone());
      } catch (_) {}
    }
    return response;
  })());
});