import { jest } from '@jest/globals';
import registerServiceWorker from '../../../src/forum/utils/registerServiceWorker';

function fakeServiceWorker(existingScope: string | null) {
  const unregister = jest.fn(() => Promise.resolve(true));
  const serviceWorker = {
    register: jest.fn(() => Promise.resolve({})),
    getRegistration: jest.fn(() => Promise.resolve(existingScope ? { scope: existingScope, unregister } : undefined)),
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
    const { serviceWorker, unregister } = fakeServiceWorker(new URL('/a/', window.location.href).href);

    registerServiceWorker('/a', false);
    await settle();

    expect(serviceWorker.register).not.toHaveBeenCalled();
    expect(unregister).toHaveBeenCalled();
  });

  test("another forum's worker on the same site is left alone", async () => {
    const { unregister } = fakeServiceWorker(new URL('/', window.location.href).href);

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
