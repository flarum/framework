import { jest } from '@jest/globals';
import { readFileSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const source = readFileSync(resolve(dirname(fileURLToPath(import.meta.url)), '../../../serviceWorker/unregister.js'), 'utf8');

describe('the service worker that removes itself', () => {
  test("it takes over at once, removes this forum's offline pages, and unregisters", async () => {
    const listeners: Record<string, (event: any) => void> = {};
    const stores = new Set([
      'flarum-offline:https://example.com/a/:v1',
      'flarum-offline:https://example.com/a/:v2',
      'flarum-offline:https://example.com/b/:v1',
      'some-extension',
    ]);

    const self = {
      registration: { scope: 'https://example.com/a/', unregister: jest.fn(() => Promise.resolve(true)) },
      addEventListener: (type: string, listener: (event: any) => void) => (listeners[type] = listener),
      skipWaiting: jest.fn(),
    };

    const caches = {
      keys: () => Promise.resolve([...stores]),
      delete: (name: string) => Promise.resolve(stores.delete(name)),
    };

    new Function('self', 'caches', source)(self, caches);

    listeners.install({});
    expect(self.skipWaiting).toHaveBeenCalled();

    let waiting: Promise<unknown> = Promise.resolve();
    listeners.activate({ waitUntil: (promise: Promise<unknown>) => (waiting = promise) });
    await waiting;

    expect([...stores].sort()).toEqual(['flarum-offline:https://example.com/b/:v1', 'some-extension']);
    expect(self.registration.unregister).toHaveBeenCalled();
  });
});
