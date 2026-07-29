import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import ExportRegistry from '../../../src/common/ExportRegistry';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

function setOnline(value: boolean): void {
  Object.defineProperty(window.navigator, 'onLine', { value, configurable: true });
}

function flushPromises(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, 0));
}

/**
 * A stand-in for webpack's script loader (`__webpack_require__.l`): invokes
 * the callback with the event produced by the next queued outcome.
 */
function makeScriptLoader(outcomes: ('load' | 'error')[]) {
  const loader = jest.fn((url: string, callback: (event: Event) => void) => {
    const outcome = outcomes.shift() ?? 'load';
    callback(new Event(outcome));
  });

  return loader;
}

beforeEach(() => {
  setOnline(true);
});

describe('ExportRegistry#loadChunk', () => {
  it('completes a successful chunk load', async () => {
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['load']);
    const done = jest.fn();

    await registry.loadChunk(original as any, '/chunk.js', done as any, 0, 'test-chunk');

    expect(original).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);
    expect((done.mock.calls[0][0] as Event).type).toBe('load');
  });

  it('reports a chunk load failure while the browser is online', async () => {
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['error']);
    const done = jest.fn();

    await registry.loadChunk(original as any, '/chunk.js', done as any, 0, 'test-chunk');

    expect(original).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);
    expect((done.mock.calls[0][0] as Event).type).toBe('error');
  });

  it('retries a chunk load that failed while offline once the connection is restored', async () => {
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['error', 'load']);
    const done = jest.fn();

    setOnline(false);
    await registry.loadChunk(original as any, '/chunk.js', done as any, 0, 'test-chunk');

    // The failure is held back, not reported.
    expect(original).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();

    setOnline(true);
    window.dispatchEvent(new Event('online'));
    await flushPromises();

    expect(original).toHaveBeenCalledTimes(2);
    expect(done).toHaveBeenCalledTimes(1);
    expect((done.mock.calls[0][0] as Event).type).toBe('load');
  });

  it('keeps retrying while the browser remains offline', async () => {
    const registry = new ExportRegistry();
    const done = jest.fn();

    // The retry fails again while offline; only the third attempt succeeds.
    const original = jest.fn((url: string, callback: (event: Event) => void) => {
      if (original.mock.calls.length < 3) {
        setOnline(false);
        callback(new Event('error'));
      } else {
        callback(new Event('load'));
      }
    });

    setOnline(false);
    await registry.loadChunk(original as any, '/chunk.js', done as any, 0, 'test-chunk');

    setOnline(true);
    window.dispatchEvent(new Event('online'));
    await flushPromises();

    expect(original).toHaveBeenCalledTimes(2);
    expect(done).not.toHaveBeenCalled();

    setOnline(true);
    window.dispatchEvent(new Event('online'));
    await flushPromises();

    expect(original).toHaveBeenCalledTimes(3);
    expect(done).toHaveBeenCalledTimes(1);
    expect((done.mock.calls[0][0] as Event).type).toBe('load');
  });
});
