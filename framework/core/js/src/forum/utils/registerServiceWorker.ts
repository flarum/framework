export default function registerServiceWorker(basePath: string, enabled: boolean): void {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

  const scope = `${basePath}/`;

  const register = () => {
    navigator.serviceWorker.register(`${basePath}/sw`, { scope }).catch((error) => {
      console.error('Could not register the service worker:', error);
    });
  };

  const unregister = () => {
    const ownScope = new URL(scope, window.location.href).href;

    navigator.serviceWorker.getRegistration(scope).then((registration) => {
      if (registration?.scope === ownScope) registration.unregister();
    });
  };

  const run = enabled ? register : unregister;

  if (document.readyState === 'complete') {
    run();
  } else {
    window.addEventListener('load', run, { once: true });
  }
}
