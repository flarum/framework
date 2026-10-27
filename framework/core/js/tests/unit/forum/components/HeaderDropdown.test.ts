import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import HeaderDropdown from '../../../../src/forum/components/HeaderDropdown';
import RequestError from '../../../../src/common/utils/RequestError';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

describe('HeaderDropdown', () => {
  test('opening it while its list fails to load leaves the error to the request', async () => {
    const dropdown = Object.create(HeaderDropdown.prototype) as HeaderDropdown;

    (dropdown as any).attrs = {
      state: { load: () => Promise.reject(new RequestError(0, '', { url: '/api/notifications' } as any, {} as any)) },
    };

    dropdown.onclick();

    await settle();
  });

  test('accepts a state whose load returns nothing', async () => {
    const dropdown = Object.create(HeaderDropdown.prototype) as HeaderDropdown;

    (dropdown as any).attrs = { state: { load: () => undefined } };

    expect(() => dropdown.onclick()).not.toThrow();

    await settle();
  });
});
