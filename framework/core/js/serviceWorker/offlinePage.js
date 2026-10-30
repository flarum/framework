// Runs in the forum's service worker, ahead of what extensions add to it, so a
// page that can't be loaded shows the forum's offline page instead of the
// browser's error. That matters most in an app opened from the home screen,
// which has no browser toolbar around the error.
// Not bundled: keep it plain JavaScript with no dependencies.
function offlinePage(options) {
  // Named after the offline page's content, which is part of this script, so
  // a changed page installs a new worker, and that worker stores the new page.
  var cacheName = 'flarum-offline-' + options.version;

  self.addEventListener('install', function (event) {
    event.waitUntil(
      caches
        .open(cacheName)
        .then(function (cache) {
          return cache.add(new Request(options.url, { cache: 'reload' }));
        })
        .then(function () {
          return self.skipWaiting();
        })
    );
  });

  self.addEventListener('activate', function (event) {
    event.waitUntil(
      Promise.all([
        caches.keys().then(function (keys) {
          return Promise.all(
            keys
              .filter(function (key) {
                return key.indexOf('flarum-offline-') === 0 && key !== cacheName;
              })
              .map(function (key) {
                return caches.delete(key);
              })
          );
        }),
        // Starts loading the page while the worker starts, so it isn't slower
        // than it would be without one.
        self.registration.navigationPreload && self.registration.navigationPreload.enable(),
      ])
    );
  });

  self.addEventListener('fetch', function (event) {
    var request = event.request;

    // Only pages: other requests, and forms being sent, go straight to the
    // network, and the app shows its own error when they fail.
    if (request.mode !== 'navigate' || request.method !== 'GET') {
      return;
    }

    event.respondWith(
      Promise.resolve(event.preloadResponse)
        .then(function (response) {
          return response || fetch(request);
        })
        .catch(function () {
          return caches.match(options.url, { cacheName: cacheName }).then(function (response) {
            return response || Response.error();
          });
        })
    );
  });
}
