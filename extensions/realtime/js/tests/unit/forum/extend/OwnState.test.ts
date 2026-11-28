import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';

import PaginatedListState from 'flarum/common/states/PaginatedListState';
import OwnState, { applyAllDiscussionsRead, applyDiscussionRead, applyNotifications } from '../../../../src/forum/extend/OwnState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.forum.pushAttributes({ apiUrl: 'https://forum.example/api' });
  OwnState();
});

beforeEach(() => {
  app.session.user = app.store.pushObject({
    type: 'users',
    id: '1',
    attributes: { unreadNotificationCount: 3, newNotificationCount: 2, markedAllAsReadAt: '2026-01-02T00:00:00+00:00' },
  } as any) as any;
});

afterEach(() => jest.restoreAllMocks());

function notification(id: string, type: string, subjectId: string, createdAt: string) {
  return app.store.pushObject({
    type: 'notifications',
    id,
    attributes: { contentType: type, createdAt, isRead: false },
    relationships: { subject: { data: { type: 'posts', id: subjectId } } },
  } as any) as any;
}

describe('notification state from another session', () => {
  test('reading one marks the others of its type about the same subject', () => {
    const one = notification('11', 'postMentioned', '5', '2026-01-01T00:00:00+00:00');
    const sameSubject = notification('12', 'postMentioned', '5', '2026-01-01T00:00:00+00:00');
    const other = notification('13', 'postMentioned', '6', '2026-01-01T00:00:00+00:00');

    applyNotifications({ read: { id: '11', type: 'postMentioned', subjectId: '5' }, unreadNotificationCount: 1, newNotificationCount: 0 });

    expect([one.isRead(), sameSubject.isRead(), other.isRead()]).toEqual([true, true, false]);
    expect(app.session.user!.unreadNotificationCount()).toBe(1);
  });

  test('reading all leaves notifications that arrived afterwards unread', () => {
    const older = notification('21', 'userMentioned', '1', '2026-01-01T00:00:00+00:00');
    const newer = notification('22', 'userMentioned', '2', '2026-01-03T00:00:00+00:00');

    applyNotifications({ allRead: '2026-01-02T00:00:00+00:00', unreadNotificationCount: 1, newNotificationCount: 1 });

    expect([older.isRead(), newer.isRead()]).toEqual([true, false]);
  });

  test('opening the list elsewhere clears the new count, and the list here reloads when next opened', () => {
    const notifications: any = app.notifications;
    notifications.pages = [{ number: 1, items: [] }];
    const clear = jest.spyOn(notifications, 'clear');
    const loadNext = jest.spyOn(PaginatedListState.prototype, 'loadNext').mockResolvedValue(undefined);

    applyNotifications({ newNotificationCount: 0 });

    expect(app.session.user!.newNotificationCount()).toBe(0);
    expect(app.session.user!.unreadNotificationCount()).toBe(3);
    // An open list stays on screen.
    expect(clear).not.toHaveBeenCalled();
    expect(notifications.pages).toHaveLength(1);

    notifications.load();
    expect(loadNext).toHaveBeenCalledTimes(1);

    notifications.pages = [{ number: 1, items: [] }];
    notifications.load();
    expect(loadNext).toHaveBeenCalledTimes(1);
  });
});

describe('discussion read state from another session', () => {
  test('moves the read position forward, never back', () => {
    const discussion = app.store.pushObject({ type: 'discussions', id: '7', attributes: { lastReadPostNumber: 3 } } as any) as any;

    applyDiscussionRead({ id: '7', lastReadPostNumber: 5, lastReadAt: null });
    expect(discussion.lastReadPostNumber()).toBe(5);

    applyDiscussionRead({ id: '7', lastReadPostNumber: 4, lastReadAt: null });
    expect(discussion.lastReadPostNumber()).toBe(5);
  });

  test('an older mark-all-read time does not replace a newer one', () => {
    applyAllDiscussionsRead({ markedAllAsReadAt: '2026-01-01T00:00:00+00:00' });
    expect(app.session.user!.markedAllAsReadAt()!.toISOString()).toBe('2026-01-02T00:00:00.000Z');

    applyAllDiscussionsRead({ markedAllAsReadAt: '2026-01-05T00:00:00+00:00' });
    expect(app.session.user!.markedAllAsReadAt()!.toISOString()).toBe('2026-01-05T00:00:00.000Z');
  });
});

describe('the socket ID sent with requests', () => {
  function headersFor(state: string, url: string): Record<string, string> {
    (app as any).websocket = { connection: { state, socket_id: '123.456' } };
    app.session.csrfToken = 'token';

    const options: any = (app as any).transformRequestOptions({ url, method: 'POST' });
    const headers: Record<string, string> = {};
    options.config({ setRequestHeader: (name: string, value: string) => (headers[name] = value) });

    return headers;
  }

  test('names this tab on API requests while connected', () => {
    expect(headersFor('connected', app.forum.attribute('apiUrl') + '/posts')['X-Socket-ID']).toBe('123.456');
  });

  test('is left off while reconnecting, when the ID is stale', () => {
    expect(headersFor('connecting', app.forum.attribute('apiUrl') + '/posts')['X-Socket-ID']).toBeUndefined();
  });

  test('is never sent elsewhere', () => {
    expect(headersFor('connected', 'https://elsewhere.example/api')['X-Socket-ID']).toBeUndefined();
  });
});
