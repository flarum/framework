import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import type User from '../../../../src/common/models/User';

beforeAll(() => bootstrapForum());

describe('User', () => {
  test('a user seen just now is online', () => {
    app.store.pushPayload({ data: { type: 'users', id: '7', attributes: { username: 'frank', lastSeenAt: new Date().toISOString() } } });

    expect(app.store.getById<User>('users', '7')!.isOnline()).toBe(true);
  });
});
