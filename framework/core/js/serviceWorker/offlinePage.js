// Not bundled: keep it plain JavaScript with no dependencies.
function offlinePage(options) {
  var prefix = 'flarum-offline:' + self.registration.scope + ':';
  var cacheName = prefix + options.version;

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
                return key.indexOf(prefix) === 0 && key !== cacheName;
              })
              .map(function (key) {
                return caches.delete(key);
              })
          );
        }),
        self.registration.navigationPreload && self.registration.navigationPreload.enable(),
      ])
    );
  });

  self.addEventListener('fetch', function (event) {
    var request = event.request;

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
