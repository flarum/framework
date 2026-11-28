import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import PageState from 'flarum/common/states/PageState';
import PostListState from 'flarum/forum/states/PostListState';
import MentionsUserPage from '../../../../src/forum/components/MentionsUserPage';
import keepNewestMentionCount from '../../../../src/forum/utils/keepNewestMentionCount';
import refreshMentionsOnPostSave from '../../../../src/forum/utils/refreshMentionsOnPostSave';
import extenders from '../../../../src/forum/extend';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  app.bootExtensions({ 'flarum-mentions': { extend: extenders } });

  keepNewestMentionCount();
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

  // A user keeps the highest count revision it has seen, so each test starts
  // from a store without the users the last one left behind.
  app.store.all<User>('users').forEach((user) => app.store.remove(user));
});

/**
 * A page of posts as the list receives it from the store, with a link to the
 * next page when there is one.
 */
function pageOf(ids: string[], hasNext = false) {
  const items = ids.map((id) => app.store.pushPayload({ data: { type: 'posts', id, attributes: { number: Number(id) } } } as any));

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
  const user = app.store.pushPayload({ data: makeUser({ id: userId, attributes: { slug: `user${userId}` } }) } as any);

  // What core's `UserPage.loadUser` does on the page state it started with:
  // records that state, then records the user it resolved, then calls `show()`.
  app.current = new PageState(MentionsUserPage, {});
  app.current.set('resolvedUser', { slug: user.slug(), user });

  const page: any = Object.create(MentionsUserPage.prototype);
  page.posts = posts;
  page.pageState = app.current;
  page.show(user);

  await flush();

  return page;
}

function post(id = '1') {
  app.store.pushPayload({
    data: { type: 'posts', id, attributes: { number: Number(id) } },
  } as any);

  return app.store.getById<Post>('posts', id)!;
}

/** A post that is not on the server yet, as the composer starts saving it. */
function newPost(number = 5) {
  return app.store.createRecord<Post>('posts', { attributes: { number } });
}

/** An update response whose `affectedMentionUsers` attribute names the users whose count changed. */
const savedPost = (affectedUsers: { id: string; mentionCount?: number }[] = []) => ({
  data: {
    type: 'posts',
    id: '1',
    attributes: { number: 1, affectedMentionUsers: affectedUsers },
  },
});

/** An update response that says nothing about the users whose count changed. */
const savedPostWithoutAffectedUsers = () => ({ data: { type: 'posts', id: '1', attributes: { number: 1 } } });

/**
 * A create response: it has no `affectedMentionUsers` (that is update-only) but
 * reports the users the new post mentions, with their side-loaded counts.
 */
const createdPost = (mentionedUsers: string[] = [], mentionCount = 1, mentionCountRevision = 1) => ({
  data: {
    type: 'posts',
    id: '50',
    attributes: { number: 5 },
    relationships: {
      mentionsUsers: { data: mentionedUsers.map((id) => ({ type: 'users', id })) },
    },
  },
  included: mentionedUsers.map((id) => ({ type: 'users', id, attributes: { username: `user${id}`, mentionCount, mentionCountRevision } })),
});

/**
 * A discussion create response: the first post and the users it mentions are
 * side-loaded, with the counts the server wrote for them.
 */
const createdDiscussion = (userId: string, mentionCount: number, mentionCountRevision: number) => ({
  data: {
    type: 'discussions',
    id: '20',
    attributes: { title: 'New' },
    relationships: { firstPost: { data: { type: 'posts', id: '60' } } },
  },
  included: [
    {
      type: 'posts',
      id: '60',
      attributes: { number: 1 },
      relationships: { mentionsUsers: { data: [{ type: 'users', id: userId }] } },
    },
    { type: 'users', id: userId, attributes: { username: `user${userId}`, mentionCount, mentionCountRevision } },
  ],
});

/** A user resource as a profile read returns it, with the count and its revision only when permitted. */
const userPayload = (id: string, mentionCount?: number, mentionCountRevision?: number) => ({
  data: {
    type: 'users',
    id,
    attributes: {
      username: `user${id}`,
      ...(mentionCount === undefined ? {} : { mentionCount }),
      ...(mentionCountRevision === undefined ? {} : { mentionCountRevision }),
    },
  },
});

describe('a Mentions tab and a saved post', () => {
  it('puts its list on the current page, with the user it belongs to, so a save can reload it', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const page = await openMentionsPage('2');

    expect(app.current.get('mentions').posts).toBe(page.posts);
    expect(app.current.get('mentions').user.id()).toBe('2');
  });

  // The page state is the one still in use, but it has since resolved another
  // profile. The recorded user catches a response the state has moved on from.
  it('ignores a profile response that arrives after the reader opened another profile', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const bobPage = await openMentionsPage('4');

    const alice = app.store.pushPayload({
      data: makeUser({ id: '9', attributes: { username: 'alice', slug: 'alice' } }),
    } as any);

    // Alice's profile was requested from her own mentions page before the
    // reader moved to Bob's, and only now comes back. The state is still the
    // one in use, so only the recorded user can tell it belongs to Alice.
    const stalePage: any = Object.create(MentionsUserPage.prototype);
    stalePage.posts = new PostListState({}, 20);
    stalePage.pageState = app.current;
    stalePage.show(alice);

    expect(app.current.get('mentions').user.id()).toBe('4');
    expect(app.current.get('mentions').posts).toBe(bobPage.posts);
    expect(app.current.get('user').id()).toBe('4');
    expect(stalePage.user).toBeUndefined();
    expect(stalePage.posts.getPages()).toHaveLength(0);
  });

  // The reader opened another profile, and its loadUser has not resolved yet:
  // the new page state has no `resolvedUser` to compare with. The state
  // identity still tells the old page that the reader has left it.
  it('ignores a profile response that arrives after the reader moved to a profile still loading', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const bobPage = await openMentionsPage('4');

    app.current = new PageState(MentionsUserPage, {});

    const bob = app.store.getById<User>('users', '4')!;

    // Bob's own mentions page was loading before the reader opened Alice's;
    // its response arrives now.
    const stalePage: any = Object.create(MentionsUserPage.prototype);
    stalePage.posts = new PostListState({}, 20);
    stalePage.pageState = bobPage.pageState;
    stalePage.show(bob);

    expect(app.current.get('user')).toBeUndefined();
    expect(app.current.get('mentions')).toBeUndefined();
    expect(stalePage.user).toBeUndefined();
    expect(stalePage.posts.getPages()).toHaveLength(0);
  });

  // The reader left profiles altogether. The page state that replaced the
  // mentions page has no `resolvedUser` either, so identity is what stops the
  // response from writing into it.
  it('ignores a profile response that arrives after the reader moved to a non-profile page', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const bobPage = await openMentionsPage('4');

    app.current = new PageState(null, {});

    const bob = app.store.getById<User>('users', '4')!;

    const stalePage: any = Object.create(MentionsUserPage.prototype);
    stalePage.posts = new PostListState({}, 20);
    stalePage.pageState = bobPage.pageState;
    stalePage.show(bob);

    expect(app.current.get('user')).toBeUndefined();
    expect(app.current.get('mentions')).toBeUndefined();
    expect(stalePage.user).toBeUndefined();
    expect(stalePage.posts.getPages()).toHaveLength(0);
  });

  // With the page still in use but no `resolvedUser` recorded, there is no
  // competing profile to protect: the page must initialise rather than wait for
  // a state that will never arrive.
  it('initialises when the page state has not resolved a user', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const user = app.store.pushPayload({
      data: makeUser({ id: '6', attributes: { slug: 'user6' } }),
    } as any);

    app.current = new PageState(MentionsUserPage, {});

    const page: any = Object.create(MentionsUserPage.prototype);
    page.posts = new PostListState({}, 20);
    page.pageState = app.current;
    page.show(user);

    await flush();

    expect(app.current.get('user').id()).toBe('6');
    expect(app.current.get('mentions').user.id()).toBe('6');
    expect(app.current.get('mentions').posts).toBe(page.posts);
  });

  // `loadUser` records the state before asking for the user, because the
  // preloaded and stored branches call `show()` synchronously.
  it('records the page state it loads the user on', async () => {
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    const user = app.store.pushPayload({
      data: makeUser({ id: '7', attributes: { slug: 'user7' } }),
    } as any);

    app.current = new PageState(MentionsUserPage, {});

    const page: any = Object.create(MentionsUserPage.prototype);
    page.posts = new PostListState({}, 20);

    await page.loadUser(user.slug());
    await flush();

    expect(page.pageState).toBe(app.current);
    expect(app.current.get('mentions').user.id()).toBe('7');
  });

  it('reloads the list when the content changes, so a post that no longer matches leaves it', async () => {
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(['1', '2']) as any)
      .mockResolvedValueOnce(pageOf(['2']) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3' }, { id: '4' }]))) as any;

    await openMentionsPage('3', posts);
    const revalidate = jest.spyOn(posts, 'revalidate');
    expect(idsInList(posts)).toEqual(['1', '2']);

    await post().save({ content: 'edited' });
    // The reload is asked for once the save resolves, and replaces the list
    // when its response arrives.
    await flush();

    expect(revalidate).toHaveBeenCalledTimes(1);
    expect(idsInList(posts)).toEqual(['2']);
  });

  // Editing a post to mention B leaves the post in A's list and in A's count,
  // so the response names only B. Reloading A's tab would drop the pages the
  // reader has loaded without anything in it changing.
  it("keeps the loaded pages, in order, when a save changes another user's mentions", async () => {
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(ids(1, 20), true) as any)
      .mockResolvedValueOnce(pageOf(ids(21, 40)) as any)
      .mockResolvedValue(pageOf([]) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '4', mentionCount: 1 }]))) as any;

    await openMentionsPage('3', posts);
    await posts.loadNext();
    const revalidate = jest.spyOn(posts, 'revalidate');
    expect(idsInList(posts)).toEqual(ids(1, 40));

    // The edited post is on the second page, which a reload would discard.
    await post('30').save({ content: 'edited to mention user 4' });
    await flush();

    expect(revalidate).not.toHaveBeenCalled();
    expect(idsInList(posts)).toEqual(ids(1, 40));
  });

  // An edit also saves `content`, but when the mention set did not change the
  // response names no affected users. Reloading then would leave only the
  // first page on screen while the count still describes all of them.
  it('keeps the loaded pages when an edit leaves the mentions unchanged', async () => {
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(ids(1, 20), true) as any)
      .mockResolvedValueOnce(pageOf(ids(21, 40)) as any)
      .mockResolvedValue(pageOf([]) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost()) as any);

    await openMentionsPage('3', posts);
    await posts.loadNext();
    const revalidate = jest.spyOn(posts, 'revalidate');
    expect(posts.getPages()).toHaveLength(2);

    await post().save({ content: 'fixed a typo' });
    await flush();

    expect(revalidate).not.toHaveBeenCalled();
    expect(idsInList(posts)).toEqual(ids(1, 40));
  });

  // The response also carries the new counts, so the tab's badge can follow the
  // save without a second request. It is sent whatever `include` the caller
  // named, which is what makes it reliable.
  it("updates an affected user's count in the store without creating missing users", async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    await openMentionsPage('3', posts);

    const user = app.store.getById<User>('users', '3')!;
    user.pushAttributes({ mentionCount: 1 });

    app.request = jest.fn(() =>
      Promise.resolve(
        savedPost([
          { id: '3', mentionCount: 0 },
          { id: '77', mentionCount: 5 },
        ])
      )
    ) as any;

    await post().save({ content: 'edited' });
    await flush();

    expect(user.attribute<number>('mentionCount')).toBe(0);
    // The response names a user this reader has not loaded; a partial record
    // would show them with a count but no profile.
    expect(app.store.getById('users', '77')).toBeUndefined();
    // The response carries the count, so the user is not read again.
    expect(app.request).toHaveBeenCalledTimes(1);
  });

  it('reloads when the response does not say which users changed', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1', '2']) as any);
    app.request = jest.fn(() => Promise.resolve(savedPostWithoutAffectedUsers()) as any);

    await openMentionsPage('3', posts);
    const revalidate = jest.spyOn(posts, 'revalidate');

    await post().save({ content: 'edited' });
    await flush();

    expect(revalidate).toHaveBeenCalledTimes(1);
  });

  // A new post has no `affectedMentionUsers` (that attribute is update-only),
  // but the create response reports who it mentions. Mentioning nobody cannot
  // change the open tab's count, so its loaded pages must stay.
  it('does not reload when a new post mentions nobody', async () => {
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(ids(1, 20), true) as any)
      .mockResolvedValueOnce(pageOf(ids(21, 40)) as any)
      .mockResolvedValue(pageOf([]) as any);
    app.request = jest.fn(() => Promise.resolve(createdPost()) as any);

    await openMentionsPage('3', posts);
    await posts.loadNext();
    const revalidate = jest.spyOn(posts, 'revalidate');
    expect(idsInList(posts)).toEqual(ids(1, 40));

    await newPost().save({ content: 'a reply that mentions nobody' });
    await flush();

    expect(revalidate).not.toHaveBeenCalled();
    expect(idsInList(posts)).toEqual(ids(1, 40));
  });

  // The create response names the users the new post mentions; one of them is
  // the user whose tab is open.
  it('reloads when a new post mentions the user whose tab is open', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1']) as any);
    app.request = jest.fn(() => Promise.resolve(createdPost(['3'])) as any);

    await openMentionsPage('3', posts);
    const revalidate = jest.spyOn(posts, 'revalidate');

    await newPost().save({ content: 'a reply mentioning user 3' });
    await flush();

    expect(revalidate).toHaveBeenCalledTimes(1);
  });

  // The list hands back a refresh that is already in flight instead of sending
  // a second request, so a save that lands during one would be missed. One
  // more refresh runs when that request settles.
  it('refreshes once more when a save lands while the list is already refreshing', async () => {
    const posts = new PostListState({}, 20);
    const find = jest.spyOn(app.store, 'find');
    find.mockResolvedValueOnce(pageOf(['1']) as any);

    await openMentionsPage('3', posts);
    await flush();
    expect(idsInList(posts)).toEqual(['1']);

    let finishFirstRefresh!: (page: any) => void;
    find.mockReturnValueOnce(new Promise((resolve) => (finishFirstRefresh = resolve)) as any);

    const revalidate = jest.spyOn(posts, 'revalidate');

    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3', mentionCount: 1 }]))) as any;
    await post('2').save({ content: 'first edit' });
    await flush();
    expect(revalidate).toHaveBeenCalledTimes(1);

    // The second save arrives before the first refresh has finished. The badge
    // already shows its count.
    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3', mentionCount: 0 }]))) as any;
    await post('3').save({ content: 'second edit' });
    await flush();

    expect(revalidate).toHaveBeenCalledTimes(1);
    expect(app.store.getById<User>('users', '3')!.attribute<number>('mentionCount')).toBe(0);

    // The first request finishes with the state from before the second save,
    // and the queued refresh then shows both posts.
    find.mockResolvedValueOnce(pageOf(['1', '2']) as any);
    finishFirstRefresh(pageOf(['1']));
    await flush();
    await flush();

    expect(revalidate).toHaveBeenCalledTimes(2);
    expect(idsInList(posts)).toEqual(['1', '2']);
  });

  // Ian's first case: a native edit removes the only mention and the badge goes
  // to 0, then a profile read that was sent before the edit arrives with 1.
  it('keeps a corrected count when an older profile read arrives after the edit', async () => {
    const realFind = app.store.find.bind(app.store);
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockImplementation(((type: string, ...rest: any[]) =>
        type === 'users' ? (realFind as any)(type, ...rest) : Promise.resolve(pageOf([]))) as any);

    await openMentionsPage('3', posts);

    const user = app.store.getById<User>('users', '3')!;
    user.pushAttributes({ mentionCount: 1, mentionCountRevision: 4 });

    let finishProfileRead!: (payload: any) => void;

    app.request = jest.fn((options: any) => {
      if (options.method === 'GET' && options.url.includes('/users/')) {
        return new Promise((resolve) => (finishProfileRead = resolve));
      }

      return Promise.resolve(savedPost([{ id: '3', mentionCount: 0, mentionCountRevision: 5 }]));
    }) as any;

    // The profile read leaves first; the server answers it before the edit.
    const profileRead = app.store.find('users', '3');
    await flush();

    await post('10').save({ content: 'edited to remove the only mention' });
    await flush();

    expect(user.mentionCount()).toBe(0);

    finishProfileRead(userPayload('3', 1, 4));
    await profileRead;
    await flush();

    expect(user.mentionCount()).toBe(0);
    expect(user.mentionCountRevision()).toBe(5);
    // The rest of the older profile still reaches the store.
    expect(user.username()).toBe('user3');
  });

  // Ian's second case: a new discussion's response carries 41, but before it
  // arrives a hide of another mentioning post brings the count to 40.
  it('keeps a newer count when a delayed discussion creation response arrives', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    await openMentionsPage('3', posts);

    const user = app.store.getById<User>('users', '3')!;
    user.pushAttributes({ mentionCount: 40, mentionCountRevision: 8 });

    let finishCreate!: (payload: any) => void;

    app.request = jest.fn((options: any) => {
      if (options.method === 'POST' && options.url.includes('/discussions')) {
        return new Promise((resolve) => (finishCreate = resolve));
      }

      return Promise.resolve(savedPost([{ id: '3', mentionCount: 40, mentionCountRevision: 10 }]));
    }) as any;

    // The server writes the creation first (revision 9, count 41) and the hide
    // second (revision 10, count 40), but the creation response is delivered
    // last.
    const create = app.store.createRecord('discussions').save({ title: 'New', content: 'Hi @user3' });
    await flush();

    await post('11').save({ isHidden: true });
    await flush();

    expect(user.mentionCount()).toBe(40);

    finishCreate(createdDiscussion('3', 41, 9));
    await create;
    await flush();

    expect(user.mentionCount()).toBe(40);
    expect(user.mentionCountRevision()).toBe(10);
  });

  // The server decides the order, not the browser: the first save is processed
  // last and carries the final count, but its response is delivered first.
  it('keeps the count the server wrote last when save responses arrive out of order', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    await openMentionsPage('3', posts);

    const user = app.store.getById<User>('users', '3')!;
    user.pushAttributes({ mentionCount: 5, mentionCountRevision: 2 });

    let finishFirstSave!: (payload: any) => void;
    let finishSecondSave!: (payload: any) => void;
    let saveCount = 0;

    app.request = jest.fn((options: any) => {
      if (options.url.includes('/users/')) return Promise.resolve(userPayload('3', 5, 2));

      saveCount++;

      return new Promise((resolve) => {
        if (saveCount === 1) finishFirstSave = resolve;
        else finishSecondSave = resolve;
      });
    }) as any;

    const firstSave = post('10').save({ content: 'first edit' });
    const secondSave = post('11').save({ content: 'second edit' });
    await flush();

    finishFirstSave(savedPost([{ id: '3', mentionCount: 0, mentionCountRevision: 4 }]));
    await firstSave;
    await flush();

    finishSecondSave(savedPost([{ id: '3', mentionCount: 1, mentionCountRevision: 3 }]));
    await secondSave;
    await flush();

    expect(user.mentionCount()).toBe(0);
    // The responses settle it: the user is not read again.
    expect((app.request as jest.Mock).mock.calls.filter(([options]: any) => options.url.includes('/users/'))).toHaveLength(0);
  });

  // A reply pushes its side-loaded users into the store as its response lands,
  // so an older count would win if the store kept the last one to arrive.
  it('a reply response arriving after a newer edit does not win the badge', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf([]) as any);

    await openMentionsPage('3', posts);

    const user = app.store.getById<User>('users', '3')!;
    user.pushAttributes({ mentionCount: 5, mentionCountRevision: 2 });

    let finishEdit!: (payload: any) => void;
    let finishReply!: (payload: any) => void;

    app.request = jest.fn(
      (options: any) =>
        new Promise((resolve) => {
          if (options.method === 'POST') finishReply = resolve;
          else finishEdit = resolve;
        })
    ) as any;

    const edit = post('10').save({ content: 'edited to remove the mention' });
    const reply = newPost(6).save({ content: 'a reply mentioning user 3' });
    await flush();

    finishEdit(savedPost([{ id: '3', mentionCount: 0, mentionCountRevision: 4 }]));
    await edit;
    await flush();

    expect(user.mentionCount()).toBe(0);

    finishReply(createdPost(['3'], 1, 3));
    await reply;
    await flush();

    expect(user.mentionCount()).toBe(0);
    expect(app.request).toHaveBeenCalledTimes(2);
  });

  // The queued refresh is only for the page that asked for it: once the reader
  // has left, there is no list on screen to bring up to date.
  it('drops a queued refresh when the reader has left the mentions page', async () => {
    const posts = new PostListState({}, 20);
    const find = jest.spyOn(app.store, 'find');
    find.mockResolvedValueOnce(pageOf(['1']) as any);

    await openMentionsPage('3', posts);
    await flush();

    let finishFirstRefresh!: (page: any) => void;
    find.mockReturnValueOnce(new Promise((resolve) => (finishFirstRefresh = resolve)) as any);

    const revalidate = jest.spyOn(posts, 'revalidate');

    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3' }]))) as any;
    await post('2').save({ content: 'first edit' });
    await flush();
    expect(revalidate).toHaveBeenCalledTimes(1);

    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3' }]))) as any;
    await post('3').save({ content: 'second edit' });
    await flush();
    expect(revalidate).toHaveBeenCalledTimes(1);

    // The reader leaves while the first refresh is still in flight.
    app.current.set('mentions', undefined);

    finishFirstRefresh(pageOf(['1']));
    await flush();
    await flush();

    // The refresh already running finishes; the queued one is not sent.
    expect(revalidate).toHaveBeenCalledTimes(1);
    expect(idsInList(posts)).toEqual(['1']);
  });

  it('reloads the list when a post is hidden or restored', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1']) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '3' }]))) as any;

    await openMentionsPage('3', posts);
    const revalidate = jest.spyOn(posts, 'revalidate');

    await post().save({ isHidden: true });
    await flush();
    expect(revalidate).toHaveBeenCalledTimes(1);

    await post().save({ isHidden: false });
    await flush();
    expect(revalidate).toHaveBeenCalledTimes(2);
  });

  // A like saves `isLiked`, which mentions know nothing about. Reloading then
  // would drop every page after the first, so liking the last post of a long
  // list would shorten it.
  it('keeps a list the reader has paged through when a save only changes something else', async () => {
    const posts = new PostListState({}, 20);
    jest
      .spyOn(app.store, 'find')
      .mockResolvedValueOnce(pageOf(ids(1, 20), true) as any)
      .mockResolvedValueOnce(pageOf(ids(21, 40)) as any)
      .mockResolvedValue(pageOf([]) as any);
    app.request = jest.fn(() => Promise.resolve(savedPost()) as any);

    await openMentionsPage('3', posts);
    await posts.loadNext();
    const revalidate = jest.spyOn(posts, 'revalidate');
    expect(posts.getPages()).toHaveLength(2);

    await post().save({ isLiked: true });

    expect(revalidate).not.toHaveBeenCalled();
    expect(posts.getPages()).toHaveLength(2);
    expect(idsInList(posts)).toContain('40');
  });

  it('keeps the list as it is when the save fails, and reports the failure', async () => {
    const posts = new PostListState({}, 20);
    jest.spyOn(app.store, 'find').mockResolvedValue(pageOf(['1']) as any);
    app.request = jest.fn(() => Promise.reject(new Error('nope'))) as any;

    await openMentionsPage('4', posts);
    const revalidate = jest.spyOn(posts, 'revalidate');

    await expect(post().save({ content: 'edited' })).rejects.toThrow('nope');

    expect(revalidate).not.toHaveBeenCalled();
    expect(idsInList(posts)).toEqual(['1']);
  });

  it('saves elsewhere on the forum without a Mentions tab to reload', async () => {
    app.request = jest.fn(() => Promise.resolve(savedPost([{ id: '4' }]))) as any;

    await expect(post().save({ content: 'edited' })).resolves.toBeDefined();
  });
});
