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

describe('ExportRegistry chunks that are not registered yet (#5103)', () => {
  // RegisterAsyncChunksPlugin appends its `addChunkModule` calls to the module
  // holding the import. When that module is itself an async chunk — a lazy
  // import inside a lazily-loaded component — the registrations only run once
  // that chunk has loaded, which is after the registry is asked for its URL.
  //
  // Webpack's own URL is no help there: with automatic publicPath it resolves
  // the chunk against the directory the entry bundle came from, so a forum
  // serving chunks from `assets/js/<namespace>/` (S3, a CDN prefix, a
  // subdirectory install) gets a URL one directory tree too shallow. On a flat
  // local-disk forum the same wrong URL happens to land on the file, which is
  // why this stayed hidden.
  //
  // The namespace is known even when the chunk is not registered, and webpack
  // names the file after the chunk's url path, so the correct URL can be
  // rebuilt from the two.
  function withForumAttributes<T>(run: () => T): T {
    const attributes: Record<string, string> = {
      jsChunksBaseUrl: 'https://cdn.example.com/assets/js',
      assetsBaseUrl: 'https://cdn.example.com/assets',
    };
    const forum = (app as any).forum;
    (app as any).forum = { attribute: (key: string) => attributes[key] };

    try {
      return run();
    } finally {
      (app as any).forum = forum;
    }
  }

  it('rebuilds the url of an unregistered chunk from its namespace', async () => {
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['load']);
    const done = jest.fn(() => Promise.resolve());

    await withForumAttributes(() =>
      registry.loadChunk(
        original as any,
        // What webpack asks for: the entry bundle's directory plus the chunk
        // file name, with no `js/<namespace>` in between.
        'https://cdn.example.com/assets/forum/components/DeckPickerModal.js',
        done as any,
        0,
        436,
        'flarum-deck'
      )
    );

    expect(original).toHaveBeenCalledWith(
      'https://cdn.example.com/assets/js/flarum-deck/forum/components/DeckPickerModal.js',
      expect.any(Function),
      0,
      436
    );
  });

  it('keeps the query string off the rebuilt url', async () => {
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['load']);
    const done = jest.fn(() => Promise.resolve());

    await withForumAttributes(() =>
      registry.loadChunk(
        original as any,
        'https://cdn.example.com/assets/forum/components/DeckPickerModal.js?v=abc123',
        done as any,
        0,
        436,
        'flarum-deck'
      )
    );

    expect(original).toHaveBeenCalledWith(
      'https://cdn.example.com/assets/js/flarum-deck/forum/components/DeckPickerModal.js',
      expect.any(Function),
      0,
      436
    );
  });

  it('leaves the url alone when no namespace was given', async () => {
    // Bundles built before the namespace was passed (flarum-webpack-config
    // 3.0.4 and earlier) have nothing to rebuild from, so the url webpack
    // computed is still the best available answer.
    const registry = new ExportRegistry();
    const original = makeScriptLoader(['load']);
    const done = jest.fn(() => Promise.resolve());

    await withForumAttributes(() =>
      registry.loadChunk(original as any, 'https://cdn.example.com/assets/forum/components/Unknown.js', done as any, 0, 436)
    );

    expect(original).toHaveBeenCalledWith('https://cdn.example.com/assets/forum/components/Unknown.js', expect.any(Function), 0, 436);
  });

  it('prefers a registered chunk over rebuilding', async () => {
    const registry = new ExportRegistry();
    registry.addChunkModule(436, 500, 'flarum-deck', 'forum/components/DeckPickerModal');

    const original = makeScriptLoader(['load']);
    const done = jest.fn(() => Promise.resolve());

    await withForumAttributes(() =>
      registry.loadChunk(original as any, 'https://cdn.example.com/assets/whatever/it/asked/for.js', done as any, 0, 436, 'flarum-deck')
    );

    expect(original).toHaveBeenCalledWith(
      'https://cdn.example.com/assets/js/flarum-deck/forum/components/DeckPickerModal.js',
      expect.any(Function),
      0,
      436
    );
  });
});

describe('ExportRegistry#asyncModuleImport', () => {
  // `import('ext:…')` compiles to asyncModuleImport, which loads the chunk the
  // module was split into. A module can just as well sit in its extension's
  // main bundle — fof/upload's Uploader does, and fof/seo imports it lazily —
  // in which case there is no chunk, but the module is already loaded.
  it('resolves a module another bundle has already loaded', async () => {
    const registry = new ExportRegistry();
    class Uploader {}

    registry.add('fof-upload', 'forum/handler/Uploader', { default: Uploader });

    const module = await registry.asyncModuleImport('ext:fof/upload/forum/handler/Uploader');

    expect(module.default).toBe(Uploader);
  });

  it('gives a loaded module without a default export the shape an imported chunk has', async () => {
    const registry = new ExportRegistry();
    const helpers = { format: () => 'formatted' };

    registry.add('acme-thing', 'forum/utils/helpers', helpers);

    const module = await registry.asyncModuleImport('ext:acme/thing/forum/utils/helpers');

    expect(module.format()).toBe('formatted');
    expect(module.default).toBe(module);
  });

  // Bundles register a module's default export itself, which need not be an
  // object that can take a `default` property.
  it('hands back a loaded export that cannot take a default property as the default', async () => {
    const registry = new ExportRegistry();
    const frozen = Object.freeze({ limit: 10 });

    registry.add('acme-thing', 'common/constants', frozen);
    registry.add('acme-thing', 'common/greeting', 'hello');

    expect((await registry.asyncModuleImport('ext:acme/thing/common/constants')).default).toBe(frozen);
    expect((await registry.asyncModuleImport('ext:acme/thing/common/greeting')).default).toBe('hello');
  });

  it('still fails for a module that is neither loaded nor in a chunk', async () => {
    const registry = new ExportRegistry();

    await expect(registry.asyncModuleImport('ext:acme/thing/forum/missing')).rejects.toThrow('No chunk found for module acme-thing:forum/missing');
  });

  it("still loads a module that is in a chunk through its build's runtime, even once it is loaded", async () => {
    const registry = new ExportRegistry();
    class Modal {}

    registry.addChunkModule(12, 34, 'acme-thing', 'forum/components/Modal');
    registry.add('acme-thing', 'forum/components/Modal', { default: Modal });

    const runtime: any = jest.fn();
    runtime.e = jest.fn(() => Promise.resolve());
    (registry as any)._webpack_runtimes['acme-thing'] = runtime;

    const module = await registry.asyncModuleImport('ext:acme/thing/forum/components/Modal');

    expect(runtime.e).toHaveBeenCalledWith('12');
    expect(runtime.mock.calls[0][0]).toBe('34');
    expect(module.default).toBe(Modal);
  });
});
