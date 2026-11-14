export default function registerServiceWorker(basePath: string, enabled: boolean): void {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

  const scope = `${basePath}/`;
  const script = `${basePath}/sw`;

  const register = () => {
    navigator.serviceWorker.register(script, { scope }).catch((error) => {
      console.error('Could not register the service worker:', error);
    });
  };

  // Only remove a worker the forum registered itself: one an extension or
  // another app registered at the same scope is left alone.
  const unregister = () => {
    const ownScope = new URL(scope, window.location.href).href;
    const ownScript = new URL(script, window.location.href).href;

    navigator.serviceWorker.getRegistration(scope).then((registration) => {
      if (!registration || registration.scope !== ownScope) return;

      const worker = registration.active || registration.waiting || registration.installing;

      if (worker?.scriptURL !== ownScript) return;

      // Unregistered from here, the worker never runs again to clear what it
      // stored, so clear its offline page too.
      registration.unregister();

      if (!('caches' in window)) return;

      const prefix = `flarum-offline:${ownScope}:`;

      caches.keys().then((keys) => Promise.all(keys.filter((key) => key.startsWith(prefix)).map((key) => caches.delete(key))));
    });
  };

  const run = enabled ? register : unregister;

  if (document.readyState === 'complete') {
    run();
  } else {
    window.addEventListener('load', run, { once: true });
  }
}
