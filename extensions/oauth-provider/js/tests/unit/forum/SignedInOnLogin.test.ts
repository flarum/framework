import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import '../../../src/forum';
import { bootForum } from './setup';

const SIGNED_IN = 'flarum-oauth-provider.signed_in';

const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

let unhandled: unknown[];
const onUnhandled = (reason: unknown) => unhandled.push(reason);

beforeEach(() => {
  bootForum();
  sessionStorage.clear();
  unhandled = [];
  process.on('unhandledRejection', onUnhandled);
});

afterEach(() => {
  process.off('unhandledRejection', onUnhandled);
  jest.restoreAllMocks();
});

test('a failed login leaves no unhandled rejection', async () => {
  const error = { status: 401 };
  jest.spyOn(app, 'request').mockImplementation(() => Promise.reject(error));

  // The caller handles the failure, as LogInModal does.
  await app.session.login({ identification: 'someone', password: 'wrong' }).catch(() => {});
  await settle();

  expect(unhandled).toEqual([]);
  expect(sessionStorage.getItem(SIGNED_IN)).toBeNull();
});

test('a successful login marks the session as signed in', async () => {
  jest.spyOn(app, 'request').mockImplementation(() => Promise.resolve({}));

  await app.session.login({ identification: 'someone', password: 'right' });
  await settle();

  expect(sessionStorage.getItem(SIGNED_IN)).toBe('1');
});
