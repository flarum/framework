// Not bundled: keep it plain JavaScript with no dependencies.
self.addEventListener('install', function () {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  var prefix = 'flarum-offline:' + self.registration.scope + ':';

  event.waitUntil(
    caches
      .keys()
      .then(function (keys) {
        return Promise.all(
          keys
            .filter(function (key) {
              return key.indexOf(prefix) === 0;
            })
            .map(function (key) {
              return caches.delete(key);
            })
        );
      })
      .then(function () {
        return self.registration.unregister();
      })
  );
});
