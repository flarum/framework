import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import MentionsUserPage from '../../../../src/forum/components/MentionsUserPage';
import refreshMentionsOnPostSave from '../../../../src/forum/utils/refreshMentionsOnPostSave';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  refreshMentionsOnPostSave();
});

let originalRequest: typeof app.request;

beforeEach(() => {
  originalRequest = app.request;
});

afterEach(() => {
  app.request = originalRequest;
  app.current.set('mentions', undefined);
  jest.restoreAllMocks();
});

/**
 * A Mentions tab as `UserPage.loadUser` opens it, with the page's list kept in
 * the app's current-page state.
 */
function openMentionsPage(userId: string) {
  const user = app.store.pushPayload({ data: makeUser({ id: userId }) } as any);

  const page: any = Object.create(MentionsUserPage.prototype);
  page.posts = { refreshParams: jest.fn(), revalidate: jest.fn() };
  page.show(user);

  return page;
}

function post() {
  app.store.pushPayload({
    data: { type: 'posts', id: '1', attributes: { number: 1 } },
  } as any);

  return app.store.getById<Post>('posts', '1')!;
}

const savedPost = () => ({ data: { type: 'posts', id: '1', attributes: { number: 1 } } });

describe('a Mentions tab and a saved post', () => {
  it('puts its list on the current page, so a save can reload it', () => {
    const page = openMentionsPage('2');

    expect(app.current.get('mentions')).toBe(page.posts);
  });

  it('reloads the list when a post is saved', async () => {
    const page = openMentionsPage('3');
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await post().save({ content: 'edited' });

    expect(page.posts.revalidate).toHaveBeenCalledTimes(1);
  });

  it('keeps the list as it is when the save fails, and reports the failure', async () => {
    const page = openMentionsPage('4');
    app.request = jest.fn(() => Promise.reject(new Error('nope'))) as any;

    await expect(post().save({ content: 'edited' })).rejects.toThrow('nope');

    expect(page.posts.revalidate).not.toHaveBeenCalled();
  });

  it('saves elsewhere on the forum without a Mentions tab to reload', async () => {
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await expect(post().save({ content: 'edited' })).resolves.toBeDefined();
  });
});
