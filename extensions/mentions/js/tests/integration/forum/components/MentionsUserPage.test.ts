import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import PostListState from 'flarum/forum/states/PostListState';
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
 * A page of posts as the list receives it from the store, with a link to the
 * next page when there is one.
 */
function pageOf(ids: string[], hasNext = false) {
  const items = ids.map((id) =>
    app.store.pushPayload({ data: { type: 'posts', id, attributes: { number: Number(id) } } } as any)
  );

  return Object.assign(items, { payload: { data: [], links: hasNext ? { next: '/api/posts?page=2' } : {} } }) as any;
}

const ids = (from: number, to: number) => Array.from({ length: to - from + 1 }, (_, i) => String(from + i));

const idsInList = (posts: PostListState) => posts.getPages().flatMap((page: any) => page.items.map((post: Post) => post.id()));

/** Let the first page, which `show()` asks for without waiting, arrive. */
const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

/**
 * A Mentions tab as `UserPage.loadUser` opens it, with the page's list in the
 * app's current-page state.
 */
async function openMentionsPage(userId: string, posts = new PostListState({}, 20)) {
  const user = app.store.pushPayload({ data: makeUser({ id: userId }) } as any);

  const page: any = Object.create(MentionsUserPage.prototype);
  page.posts = posts;
  page.show(user);

  await flush();

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
  it('puts its list on the current page, so a save can reload it', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const page = await openMentionsPage('2');

    expect(app.current.get('mentions')).toBe(page.posts);
  });

  it('reloads the list when the content changes, so a post that no longer matches leaves it', async () => {
    const posts = new PostListState({}, 20);
    const find = jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(['1', '2']) as any)
      .mockResolvedValueOnce(pageOf(['2']) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await openMentionsPage('3', posts);
    expect(idsInList(posts)).toEqual(['1', '2']);

    await post().save({ content: 'edited' });
    // The reload is asked for once the save resolves, and replaces the list
    // when its response arrives.
    await flush();

    expect(find).toHaveBeenCalledTimes(2);
    expect(idsInList(posts)).toEqual(['2']);
  });

  it('reloads the list when a post is hidden or restored', async () => {
    const posts = new PostListState({}, 20);
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1']) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await openMentionsPage('3', posts);

    await post().save({ isHidden: true });
    await flush();
    expect(find).toHaveBeenCalledTimes(2);

    await post().save({ isHidden: false });
    await flush();
    expect(find).toHaveBeenCalledTimes(3);
  });

  // A like saves `isLiked`, which mentions know nothing about. Reloading then
  // would drop every page after the first, so liking the last post of a long
  // list would shorten it.
  it('keeps a list the reader has paged through when a save only changes something else', async () => {
    const posts = new PostListState({}, 20);
    const find = jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(ids(1, 20), true) as any)
      .mockResolvedValueOnce(pageOf(ids(21, 40)) as any)
      .mockResolvedValue(pageOf([]) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await openMentionsPage('3', posts);
    await posts.loadNext();
    expect(posts.getPages()).toHaveLength(2);

    await post().save({ isLiked: true });

    expect(app.request).toHaveBeenCalledTimes(1);
    expect(find).toHaveBeenCalledTimes(2);
    expect(posts.getPages()).toHaveLength(2);
    expect(idsInList(posts)).toContain('40');
  });

  it('keeps the list as it is when the save fails, and reports the failure', async () => {
    const posts = new PostListState({}, 20);
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1']) as any);
    app.request = jest.fn(() => Promise.reject(new Error('nope'))) as any;

    await openMentionsPage('4', posts);

    await expect(post().save({ content: 'edited' })).rejects.toThrow('nope');

    expect(find).toHaveBeenCalledTimes(1);
    expect(idsInList(posts)).toEqual(['1']);
  });

  it('saves elsewhere on the forum without a Mentions tab to reload', async () => {
    app.request = jest.fn(() => Promise.resolve(savedPost())) as any;

    await expect(post().save({ content: 'edited' })).resolves.toBeDefined();
  });
});
