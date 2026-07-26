import type ItemList from './ItemList';

/**
 * Schedule a callback to run when the browser is idle, falling back to a short
 * timeout where `requestIdleCallback` is unavailable (e.g. Safari).
 */
function whenIdle(callback: () => void): void {
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(callback);
  } else {
    setTimeout(callback, 200);
  }
}

/**
 * Warm the app's registered prefetch chunks in the background, once the browser
 * is idle.
 *
 * Loaders run one at a time, each waiting for the next idle period, so that
 * prefetching never competes with rendering or user interaction. Failures are
 * swallowed: a prefetch is a best-effort optimisation, and the real `import()`
 * on navigation will surface any genuine load error.
 *
 * @see Application.prefetch
 */
export default function drainPrefetchQueue(prefetch: ItemList<() => Promise<unknown>>): void {
  const loaders = prefetch.toArray();

  if (loaders.length === 0) return;

  let index = 0;

  const next = () => {
    if (index >= loaders.length) return;

    const loader = loaders[index++];

    // Best-effort: ignore errors, then queue the next loader for the following
    // idle period.
    Promise.resolve()
      .then(loader)
      .catch(() => {})
      .then(() => whenIdle(next));
  };

  whenIdle(next);
}
