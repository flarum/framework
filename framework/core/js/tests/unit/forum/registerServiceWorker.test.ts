import { jest } from '@jest/globals';
import registerServiceWorker from '../../../src/forum/utils/registerServiceWorker';

const url = (path: string) => new URL(path, window.location.href).href;

function fakeServiceWorker(existingScope: string | null, existingScript: string = url('/a/sw')) {
  const unregister = jest.fn(() => Promise.resolve(true));
  const serviceWorker = {
    register: jest.fn(() => Promise.resolve({})),
    getRegistration: jest.fn(() =>
      Promise.resolve(existingScope ? { scope: existingScope, active: { scriptURL: existingScript }, unregister } : undefined)
    ),
  };

  Object.defineProperty(navigator, 'serviceWorker', { value: serviceWorker, configurable: true });
  Object.defineProperty(window, 'isSecureContext', { value: true, configurable: true });

  return { serviceWorker, unregister };
}

const settle = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('registering the service worker', () => {
  afterEach(() => {
    delete (navigator as any).serviceWorker;
  });

  test('it is registered at the forum root when the forum wants one', () => {
    const { serviceWorker } = fakeServiceWorker(null);

    registerServiceWorker('/a', true);

    expect(serviceWorker.register).toHaveBeenCalledWith('/a/sw', { scope: '/a/' });
  });

  test("when the forum doesn't want one, its own registration is removed", async () => {
    const { serviceWorker, unregister } = fakeServiceWorker(url('/a/'));

    registerServiceWorker('/a', false);
    await settle();

    expect(serviceWorker.register).not.toHaveBeenCalled();
    expect(unregister).toHaveBeenCalled();
  });

  test("another forum's worker on the same site is left alone", async () => {
    const { unregister } = fakeServiceWorker(url('/'), url('/sw'));

    registerServiceWorker('/a', false);
    await settle();

    expect(unregister).not.toHaveBeenCalled();
  });

  test("a worker an extension registered at the forum's scope is left alone", async () => {
    const { unregister } = fakeServiceWorker(url('/a/'), url('/a/push-worker.js'));

    registerServiceWorker('/a', false);
    await settle();

    expect(unregister).not.toHaveBeenCalled();
  });

  test('nothing happens outside a secure context', () => {
    const { serviceWorker } = fakeServiceWorker(null);
    Object.defineProperty(window, 'isSecureContext', { value: false, configurable: true });

    registerServiceWorker('/a', true);

    expect(serviceWorker.register).not.toHaveBeenCalled();
  });
});
