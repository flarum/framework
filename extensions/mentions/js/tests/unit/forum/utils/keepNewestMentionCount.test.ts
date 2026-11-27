import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import type User from 'flarum/common/models/User';
import keepNewestMentionCount from '../../../../src/forum/utils/keepNewestMentionCount';
import extenders from '../../../../src/forum/extend';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.bootExtensions({ 'flarum-mentions': { extend: extenders } });

  keepNewestMentionCount();
});

afterEach(() => {
  app.store.all<User>('users').forEach((user) => app.store.remove(user));
});

/** Push a user resource the way any API response would. */
function receive(attributes: Record<string, unknown>): User {
  return app.store.pushPayload<User>({ data: { type: 'users', id: '5', attributes } } as any);
}

describe('a user record receiving mention counts in any order', () => {
  it('drops a count whose revision is older than the one it holds, and applies the rest', () => {
    const user = receive({ username: 'alice', mentionCount: 0, mentionCountRevision: 7 });

    receive({ username: 'alice2', mentionCount: 3, mentionCountRevision: 6 });

    expect(user.mentionCount()).toBe(0);
    expect(user.mentionCountRevision()).toBe(7);
    expect(user.username()).toBe('alice2');
  });

  it('takes a count with a newer revision', () => {
    const user = receive({ mentionCount: 3, mentionCountRevision: 6 });

    receive({ mentionCount: 0, mentionCountRevision: 7 });

    expect(user.mentionCount()).toBe(0);
    expect(user.mentionCountRevision()).toBe(7);
  });

  it('takes a count with the same revision', () => {
    const user = receive({ mentionCount: 3, mentionCountRevision: 6 });

    receive({ mentionCount: 3, mentionCountRevision: 6 });

    expect(user.mentionCount()).toBe(3);
  });

  it('takes a count when there is no revision to compare', () => {
    const user = receive({ mentionCount: 3 });

    receive({ mentionCount: 2, mentionCountRevision: 1 });
    expect(user.mentionCount()).toBe(2);

    user.pushAttributes({ mentionCount: 1 });
    expect(user.mentionCount()).toBe(1);
  });

  it('leaves the response payload as it arrived', () => {
    receive({ mentionCount: 0, mentionCountRevision: 7 });

    const payload = { data: { type: 'users', id: '5', attributes: { mentionCount: 3, mentionCountRevision: 6 } } };
    app.store.pushPayload(payload as any);

    expect(payload.data.attributes).toEqual({ mentionCount: 3, mentionCountRevision: 6 });
  });
});
