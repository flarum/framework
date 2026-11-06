import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import Discussion from '../../../../src/common/models/Discussion';
import { makeDiscussion } from '../../../factory';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

beforeEach(() => {
  app.store.data = {};
});

function deferred<T>() {
  let resolve!: (value: T) => void;
  const promise = new Promise<T>((r) => {
    resolve = r;
  });
  return { promise, resolve };
}

/**
 * `markAllAsRead()` must act on the list it was called for, not on whatever
 * `this` happens to hold once the request resolves. `DiscussionListState` is
 * shared across navigation — switching tags mid-request swaps `pages` on the
 * very same instance — so reading `getAllItems()` only in the `.then()`
 * callback would mark the *new* list's discussions as read on the client,
 * disagreeing with what the server was actually asked to mark.
 */
describe('DiscussionListState.markAllAsRead', () => {
  test('marks the discussions captured before the request was sent, not the ones in place when it resolves', async () => {
    const original = app.store.pushObject<Discussion>(makeDiscussion({ id: '1', attributes: { lastPostNumber: 5 } }))!;
    const swappedIn = app.store.pushObject<Discussion>(makeDiscussion({ id: '2', attributes: { lastPostNumber: 9 } }))!;

    const state = new DiscussionListState({});
    (state as any).pages = [{ number: 0, items: [original] }];

    const { promise, resolve } = deferred<any>();
    const requestSpy = jest.spyOn(app, 'request').mockReturnValue(promise);

    const result = state.markAllAsRead();

    // The reader navigates to a different list while the request is still in
    // flight — the same state object now holds different discussions.
    (state as any).pages = [{ number: 0, items: [swappedIn] }];

    resolve({});
    await result;

    expect(original.lastReadPostNumber()).toBe(5);
    expect(swappedIn.lastReadPostNumber()).toBeUndefined();

    requestSpy.mockRestore();
  });
});
