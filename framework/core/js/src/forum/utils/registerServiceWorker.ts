/**
 * Registers the forum's service worker, which shows the offline page when a
 * page can't be loaded and runs what extensions add to it. Extensions reach it
 * through `navigator.serviceWorker.ready`.
 *
 * Browsers only allow service workers on HTTPS and localhost.
 */
export default function registerServiceWorker(basePath: string): void {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

  const register = () => {
    navigator.serviceWorker.register(`${basePath}/sw`, { scope: `${basePath}/` }).catch((error) => {
      console.error('Could not register the service worker:', error);
    });
  };

  // Once the page has loaded, so it doesn't compete with it.
  if (document.readyState === 'complete') {
    register();
  } else {
    window.addEventListener('load', register, { once: true });
  }
}
