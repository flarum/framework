import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../src/forum/app';
import ForumApplication from '../../../src/forum/ForumApplication';
import Application from '../../../src/common/Application';
import RequestError from '../../../src/common/utils/RequestError';

// The bootstrap stubs `mount`, so keep the real one to set up the home link.
const mount = ForumApplication.prototype.mount;

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => jest.restoreAllMocks());

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

describe('ForumApplication home link', () => {
  test("clicking it while the user can't be reloaded leaves the error to the request", async () => {
    // Only the home link is under test: the router and the header aren't.
    jest.spyOn(Application.prototype, 'mount').mockImplementation(() => {});
    jest.spyOn(m, 'mount').mockImplementation(() => {});
    jest.spyOn(app.history, 'home').mockImplementation(() => {});

    const homeLink = document.createElement('a');
    homeLink.id = 'home-link';
    document.body.appendChild(homeLink);

    mount.call(app);

    jest.spyOn(app.store, 'find').mockImplementation(() => Promise.reject(new RequestError(0, '', { url: '/api/users/1' } as any, {} as any)));

    homeLink.click();

    expect(app.store.find).toHaveBeenCalledWith('users', '1');

    await settle();
  });
});
