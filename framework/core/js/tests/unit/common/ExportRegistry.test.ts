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

let warnSpy: ReturnType<typeof jest.spyOn>;

beforeEach(() => {
  setOnline(true);

  // The tests don't register any chunks, so the registry legitimately warns
  // that it has no URL for the chunk and falls back to the one we pass in.
  warnSpy = jest.spyOn(console, 'warn').mockImplementation(() => {});
});

afterEach(() => {
  warnSpy.mockRestore();
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

    // An unregistered chunk warns and falls back to the URL passed in.
    expect(warnSpy).toHaveBeenCalledWith(expect.stringContaining('No chunk by the ID'));
    expect(original).toHaveBeenCalledWith('/chunk.js', expect.any(Function), 0, 'test-chunk');
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

describe('ExportRegistry chunk ids shared between builds (#5027)', () => {
  // Chunk ids are hashed per build, so two extensions can both emit a chunk
  // 371. Resolved by id alone, one of them loaded the other's file.
  function registryWithCollision(): ExportRegistry {
    const registry = new ExportRegistry();

    registry.addChunkModule(371, 440, 'flarum-tags', 'forum/components/TagDiscussionModal');
    registry.addChunkModule(371, 440, 'flarum-tags', 'common/components/TagSelectionModal');
    registry.addChunkModule(371, 544, 'flarum-deck', 'forum/utils/loadSortable');

    return registry;
  }

  it('resolves each namespace to its own chunk', () => {
    const registry = registryWithCollision();

    expect(registry.getChunk(371, 'flarum-tags')?.urlPath).toBe('forum/components/TagDiscussionModal');
    expect(registry.getChunk(371, 'flarum-deck')?.urlPath).toBe('forum/utils/loadSortable');
  });

  it("keeps each namespace's modules on its own chunk", () => {
    const registry = registryWithCollision();

    expect(registry.getChunk(371, 'flarum-tags')?.modules).toEqual(['forum/components/TagDiscussionModal', 'common/components/TagSelectionModal']);
    expect(registry.getChunk(371, 'flarum-deck')?.modules).toEqual(['forum/utils/loadSortable']);
  });

  it('matches older bundles, which send no namespace, by the file name webpack asked for', () => {
    const registry = registryWithCollision();

    expect(registry.getChunk(371, undefined, 'https://example.com/assets/forum/utils/loadSortable.js')?.namespace).toBe('flarum-deck');
    expect(registry.getChunk(371, undefined, 'https://example.com/assets/forum/components/TagDiscussionModal.js?v=1')?.namespace).toBe('flarum-tags');
  });

  it('falls back to the first chunk registered under the id', () => {
    const registry = registryWithCollision();

    expect(registry.getChunk(371)?.namespace).toBe('flarum-tags');
    expect(registry.getChunk(371, undefined, 'https://example.com/assets/forum/unknown.js')?.namespace).toBe('flarum-tags');
  });

  it('loads a colliding chunk from the extension asking for it', async () => {
    const registry = registryWithCollision();
    const original = makeScriptLoader(['load']);
    const done = jest.fn(() => Promise.resolve());

    const attributes: Record<string, string> = {
      jsChunksBaseUrl: 'https://cdn.example.com/assets/js',
      assetsBaseUrl: 'https://cdn.example.com/assets',
    };
    const forum = (app as any).forum;
    (app as any).forum = { attribute: (key: string) => attributes[key] };

    try {
      await registry.loadChunk(original as any, 'https://cdn.example.com/assets/forum/utils/loadSortable.js', done as any, 0, 371, 'flarum-deck');
    } finally {
      (app as any).forum = forum;
    }

    expect(original).toHaveBeenCalledWith('https://cdn.example.com/assets/js/flarum-deck/forum/utils/loadSortable.js', expect.any(Function), 0, 371);
  });
});
