export default function registerServiceWorker(basePath: string): void {
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

  const register = () => {
    navigator.serviceWorker.register(`${basePath}/sw`, { scope: `${basePath}/` }).catch((error) => {
      console.error('Could not register the service worker:', error);
    });
  };

  if (document.readyState === 'complete') {
    register();
  } else {
    window.addEventListener('load', register, { once: true });
  }
}
