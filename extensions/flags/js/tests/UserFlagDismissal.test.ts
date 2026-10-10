import { jest } from '@jest/globals';
import m from 'mithril';
import app from 'flarum/forum/app';
import { bootFlags, resources, flag, listState } from './fixtures';

beforeEach(bootFlags);
afterEach(() => jest.restoreAllMocks());

describe('dismissing reports about an account', () => {
  it('DELETEs the account endpoint and removes every report for that target from all pages while preserving post reports', async () => {
    const { targetUser, post } = resources();
    const first = flag('301', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const second = flag('302', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const postFlag = flag('303', { post: { data: { type: 'posts', id: '11' } }, targetUser: { data: null } });
    targetUser.pushData({
      relationships: {
        flags: {
          data: [
            { type: 'flags', id: '301' },
            { type: 'flags', id: '302' },
          ],
        },
      },
    });
    post.pushData({ relationships: { flags: { data: [{ type: 'flags', id: '303' }] } } });
    const state = listState([first, postFlag]);
    (state as any).pages.push({ number: 2, items: [second] });
    app.forum.pushAttributes({ flagCount: 2 });
    const request = jest.spyOn(app, 'request').mockResolvedValue({});
    jest.spyOn(m, 'redraw').mockImplementation(() => {});

    await state.dismissUser(targetUser);

    expect(request).toHaveBeenCalledWith(expect.objectContaining({ method: 'DELETE', url: 'https://forum.example/api/users/5/flags' }));
    expect(targetUser.flags()).toEqual([]);
    expect(state.getPages().flatMap((page) => page.items)).toEqual([postFlag]);
    expect(post.flags()).toEqual([postFlag]);
    // The badge counts targets, not individual reports: two account reports
    // are one target, and a surviving flagged post is the other target.
    expect(app.forum.attribute('flagCount')).toBe(1);
  });

  it('keeps cached reports intact when account dismissal fails', async () => {
    const { targetUser } = resources();
    const accountFlag = flag('304', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    targetUser.pushData({ relationships: { flags: { data: [{ type: 'flags', id: '304' }] } } });
    const state = listState([accountFlag]);
    app.forum.pushAttributes({ flagCount: 1 });
    const failure = new Error('403 Forbidden');
    jest.spyOn(app, 'request').mockRejectedValue(failure);
    jest.spyOn(m, 'redraw').mockImplementation(() => {});

    await expect(state.dismissUser(targetUser)).rejects.toBe(failure);

    expect(targetUser.flags()).toEqual([accountFlag]);
    expect(state.getPages().flatMap((page) => page.items)).toEqual([accountFlag]);
    expect(app.forum.attribute('flagCount')).toBe(1);
  });

  it('preserves a flagged post whose numeric ID happens to equal the dismissed account ID', async () => {
    const { targetUser, post } = resources();
    const collidingPost = app.store.pushPayload({ data: { ...post.data, id: '5' } });
    const accountFlag = flag('305', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const postFlag = flag('306', { post: { data: { type: 'posts', id: '5' } }, targetUser: { data: null } });
    const state = listState([accountFlag, postFlag]);
    app.forum.pushAttributes({ flagCount: 2 });
    jest.spyOn(app, 'request').mockResolvedValue({});
    jest.spyOn(m, 'redraw').mockImplementation(() => {});

    await state.dismissUser(targetUser);

    expect(state.getPages().flatMap((page) => page.items)).toEqual([postFlag]);
    expect(postFlag.post()).toBe(collidingPost);
    expect(app.forum.attribute('flagCount')).toBe(1);
  });

  it('does not decrement the grouped badge again when a dismissal is repeated after its reports were cleared', async () => {
    const { targetUser } = resources();
    const accountFlag = flag('307', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const state = listState([accountFlag]);
    app.forum.pushAttributes({ flagCount: 2 });
    jest.spyOn(app, 'request').mockResolvedValue({});
    jest.spyOn(m, 'redraw').mockImplementation(() => {});

    await state.dismissUser(targetUser);
    await state.dismissUser(targetUser);

    expect(app.forum.attribute('flagCount')).toBe(1);
  });

  it('rejects an unsaved account without issuing a request to an invalid endpoint', async () => {
    const unsaved = app.store.createRecord('users');
    const state = listState([]);
    const request = jest.spyOn(app, 'request').mockResolvedValue({});

    await expect(state.dismissUser(unsaved)).rejects.toThrow('unsaved user');

    expect(request).not.toHaveBeenCalled();
  });
});
