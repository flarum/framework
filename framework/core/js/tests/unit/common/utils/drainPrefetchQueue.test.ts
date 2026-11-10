import { jest } from '@jest/globals';
import drainPrefetchQueue from '../../../../src/common/utils/drainPrefetchQueue';
import ItemList from '../../../../src/common/utils/ItemList';

type Loader = () => Promise<unknown>;

function queueOf(...loaders: Loader[]): ItemList<Loader> {
  const list = new ItemList<Loader>();
  loaders.forEach((loader, i) => list.add(`loader${i}`, loader, -i));
  return list;
}

/**
 * `drainPrefetchQueue` schedules work via `requestIdleCallback` (or a
 * `setTimeout` fallback). We install a synchronous stub for the idle callback
 * so the queue drains deterministically within the test.
 */
describe('drainPrefetchQueue', () => {
  let originalRIC: typeof window.requestIdleCallback | undefined;

  const useSyncIdle = () => {
    (window as any).requestIdleCallback = (cb: () => void) => {
      cb();
      return 0;
    };
  };

  beforeEach(() => {
    originalRIC = window.requestIdleCallback;
  });

  afterEach(() => {
    if (originalRIC) {
      window.requestIdleCallback = originalRIC;
    } else {
      delete (window as any).requestIdleCallback;
    }
    jest.useRealTimers();
  });

  test('does nothing for an empty queue', () => {
    const ric = jest.fn();
    (window as any).requestIdleCallback = ric;

    drainPrefetchQueue(new ItemList<Loader>());

    expect(ric).not.toHaveBeenCalled();
  });

  test('runs every registered loader', async () => {
    useSyncIdle();

    const a = jest.fn(() => Promise.resolve());
    const b = jest.fn(() => Promise.resolve());
    const c = jest.fn(() => Promise.resolve());

    drainPrefetchQueue(queueOf(a, b, c));
    // Let the promise chain settle between loaders.
    await new Promise((r) => setTimeout(r, 0));

    expect(a).toHaveBeenCalledTimes(1);
    expect(b).toHaveBeenCalledTimes(1);
    expect(c).toHaveBeenCalledTimes(1);
  });

  test('runs loaders one at a time, waiting for each idle period', async () => {
    const idleCallbacks: Array<() => void> = [];
    (window as any).requestIdleCallback = (cb: () => void) => {
      idleCallbacks.push(cb);
      return 0;
    };

    const a = jest.fn(() => Promise.resolve());
    const b = jest.fn(() => Promise.resolve());

    drainPrefetchQueue(queueOf(a, b));

    // First idle period scheduled, nothing run yet.
    expect(idleCallbacks).toHaveLength(1);
    expect(a).not.toHaveBeenCalled();

    // Fire the first idle period → first loader runs, next idle scheduled.
    idleCallbacks.shift()!();
    await new Promise((r) => setTimeout(r, 0));

    expect(a).toHaveBeenCalledTimes(1);
    expect(b).not.toHaveBeenCalled();
    expect(idleCallbacks).toHaveLength(1);

    // Fire the second idle period → second loader runs.
    idleCallbacks.shift()!();
    await new Promise((r) => setTimeout(r, 0));

    expect(b).toHaveBeenCalledTimes(1);
  });

  test('a rejected loader does not stop the rest', async () => {
    useSyncIdle();

    const failing = jest.fn(() => Promise.reject(new Error('chunk load failed')));
    const after = jest.fn(() => Promise.resolve());

    drainPrefetchQueue(queueOf(failing, after));
    await new Promise((r) => setTimeout(r, 0));

    expect(failing).toHaveBeenCalledTimes(1);
    expect(after).toHaveBeenCalledTimes(1);
  });

  test('falls back to setTimeout when requestIdleCallback is unavailable', async () => {
    delete (window as any).requestIdleCallback;
    jest.useFakeTimers();

    const a = jest.fn(() => Promise.resolve());

    drainPrefetchQueue(queueOf(a));

    // Nothing runs synchronously; it is queued behind the timeout.
    expect(a).not.toHaveBeenCalled();

    jest.runOnlyPendingTimers();
    // Flush the microtask that invokes the loader.
    await Promise.resolve();

    expect(a).toHaveBeenCalledTimes(1);
  });
});
