import type ItemList from './ItemList';
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
export default function drainPrefetchQueue(prefetch: ItemList<() => Promise<unknown>>): void;
