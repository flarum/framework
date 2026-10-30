import { jest } from '@jest/globals';
import { readFileSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const source = readFileSync(resolve(dirname(fileURLToPath(import.meta.url)), '../../../serviceWorker/offlinePage.js'), 'utf8');

type Listener = (event: any) => void;

class FakeRequest {
  constructor(public url: string, public init: RequestInit = {}) {}
}

const errorResponse = { type: 'error' };

function worker({
  scope = 'https://example.com/a/',
  cached = {} as Record<string, Record<string, unknown>>,
  network = (request: any): Promise<unknown> => Promise.resolve({ from: 'network', request }),
} = {}) {
  const listeners: Record<string, Listener> = {};
  const stores = new Map<string, Map<string, unknown>>(Object.entries(cached).map(([name, entries]) => [name, new Map(Object.entries(entries))]));
  const added: FakeRequest[] = [];

  const self = {
    registration: { scope, navigationPreload: { enable: jest.fn(() => Promise.resolve()) } },
    addEventListener: (type: string, listener: Listener) => (listeners[type] = listener),
    skipWaiting: jest.fn(() => Promise.resolve()),
  };

  const caches = {
    open: (name: string) => {
      if (!stores.has(name)) stores.set(name, new Map());

      return Promise.resolve({
        add: (request: FakeRequest) => {
          added.push(request);
          stores.get(name)!.set(request.url, { from: 'cache', url: request.url });

          return Promise.resolve();
        },
      });
    },
    keys: () => Promise.resolve([...stores.keys()]),
    delete: (name: string) => Promise.resolve(stores.delete(name)),
    match: (url: string, { cacheName }: { cacheName: string }) => Promise.resolve(stores.get(cacheName)?.get(url)),
  };

  const fetch = jest.fn(network);

  new Function('self', 'caches', 'fetch', 'Request', 'Response', `${source}\nreturn offlinePage;`)(self, caches, fetch, FakeRequest, {
    error: () => errorResponse,
  })({ url: '/a/offline', version: 'v2' });

  function extendable(extra: Record<string, unknown> = {}) {
    const waiting: Promise<unknown>[] = [];

    return { event: { ...extra, waitUntil: (promise: Promise<unknown>) => waiting.push(promise) }, done: () => Promise.all(waiting) };
  }

  return {
    self,
    stores,
    added,
    fetch,
    async install() {
      const { event, done } = extendable();
      listeners.install(event);
      await done();
    },
    async activate() {
      const { event, done } = extendable();
      listeners.activate(event);
      await done();
    },
    navigate(request: Record<string, unknown>, preloadResponse?: Promise<unknown>) {
      let responded: Promise<unknown> | undefined;

      listeners.fetch({
        request: { mode: 'navigate', method: 'GET', ...request },
        preloadResponse,
        respondWith: (promise: Promise<unknown>) => (responded = promise),
      });

      return responded;
    },
  };
}

describe('the offline page in the service worker', () => {
  test('installing stores the offline page fresh from the network, in a cache of its own scope', async () => {
    const sw = worker();

    await sw.install();

    expect(sw.added).toHaveLength(1);
    expect(sw.added[0].url).toBe('/a/offline');
    expect(sw.added[0].init).toEqual({ cache: 'reload' });
    expect([...sw.stores.keys()]).toEqual(['flarum-offline:https://example.com/a/:v2']);
    expect(sw.self.skipWaiting).toHaveBeenCalled();
  });

  test("activating removes this forum's outdated offline pages, and no one else's", async () => {
    const sw = worker({
      cached: {
        'flarum-offline:https://example.com/a/:v1': {},
        'flarum-offline:https://example.com/a/:v2': {},
        'flarum-offline:https://example.com/b/:v1': {},
        'some-extension': {},
      },
    });

    await sw.activate();

    expect([...sw.stores.keys()].sort()).toEqual([
      'flarum-offline:https://example.com/a/:v2',
      'flarum-offline:https://example.com/b/:v1',
      'some-extension',
    ]);
  });

  test('activating turns navigation preload on', async () => {
    const sw = worker();

    await sw.activate();

    expect(sw.self.registration.navigationPreload.enable).toHaveBeenCalled();
  });

  test('requests other than page loads are left to the network', () => {
    const sw = worker();

    expect(sw.navigate({ mode: 'cors' })).toBeUndefined();
    expect(sw.navigate({ mode: 'no-cors' })).toBeUndefined();
    expect(sw.navigate({ method: 'POST' })).toBeUndefined();
    expect(sw.fetch).not.toHaveBeenCalled();
  });

  test('a page that loads is shown as it is', async () => {
    const sw = worker();

    await expect(sw.navigate({ url: '/a/d/1' })).resolves.toEqual({ from: 'network', request: expect.objectContaining({ url: '/a/d/1' }) });
  });

  test('a preloaded page is used without fetching it again', async () => {
    const sw = worker();

    await expect(sw.navigate({ url: '/a/d/1' }, Promise.resolve({ from: 'preload' }))).resolves.toEqual({ from: 'preload' });
    expect(sw.fetch).not.toHaveBeenCalled();
  });

  test('error responses from the server are shown, not the offline page', async () => {
    const sw = worker({ network: () => Promise.resolve({ status: 503 }) });

    await sw.install();

    await expect(sw.navigate({ url: '/a/d/1' })).resolves.toEqual({ status: 503 });
  });

  test("a page that can't be loaded shows the stored offline page", async () => {
    const sw = worker({ network: () => Promise.reject(new TypeError('Failed to fetch')) });

    await sw.install();

    await expect(sw.navigate({ url: '/a/d/1' })).resolves.toEqual({ from: 'cache', url: '/a/offline' });
  });

  test('a failed preload falls back to the offline page too', async () => {
    const sw = worker({ network: () => Promise.reject(new TypeError('Failed to fetch')) });

    await sw.install();

    await expect(sw.navigate({ url: '/a/d/1' }, Promise.reject(new TypeError('Failed to fetch')))).resolves.toEqual({
      from: 'cache',
      url: '/a/offline',
    });
  });

  test("without a stored offline page, the browser's own error is shown", async () => {
    const sw = worker({ network: () => Promise.reject(new TypeError('Failed to fetch')) });

    await expect(sw.navigate({ url: '/a/d/1' })).resolves.toBe(errorResponse);
  });
});
