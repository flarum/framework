import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import PostStreamState from '../../../../src/forum/states/PostStreamState';
import Discussion from '../../../../src/common/models/Discussion';
import Post from '../../../../src/common/models/Post';
import RequestError from '../../../../src/common/utils/RequestError';

beforeAll(() => bootstrapForum());

afterEach(() => jest.restoreAllMocks());

let nextId = 10000;

/** A discussion of `postCount` posts, the first `loadedCount` of them in the store. */
function stream(postCount: number, loadedCount: number): PostStreamState {
  const base = nextId;
  nextId += postCount + 1;

  const discussionId = String(base);
  const postIds = Array.from({ length: postCount }, (_, i) => String(base + 1 + i));

  app.store.pushPayload({
    data: {
      id: discussionId,
      type: 'discussions',
      attributes: { title: 'Discussion title' },
      relationships: { posts: { data: postIds.map((id) => ({ id, type: 'posts' })) } },
    },
  });

  app.store.pushPayload({
    data: postIds.slice(0, loadedCount).map((id, i) => ({
      id,
      type: 'posts',
      attributes: { number: i + 1, contentType: 'comment', canEdit: false, createdAt: new Date(), contentHtml: '<p></p>' },
      relationships: { discussion: { data: { id: discussionId, type: 'discussions' } } },
    })),
  });

  const discussion = app.store.getById<Discussion>('discussions', discussionId)!;
  const loaded = postIds.slice(0, loadedCount).map((id) => app.store.getById<Post>('posts', id)!);

  return new PostStreamState(discussion, loaded);
}

/** Every request fails as one does offline: the request's own alert has been shown. */
function failRequests(): void {
  jest.spyOn(app.store, 'find').mockImplementation(() => Promise.reject(new RequestError(0, '', { url: '/api/posts' } as any, {} as any)));
}

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

describe('PostStreamState when a load fails', () => {
  test('a page that fails to load leaves the stream free to load the next one', async () => {
    const state = stream(40, 20);
    failRequests();

    state.loadPage(20, 40);
    await settle();

    // Scroll loading waits while any page is loading.
    expect(state.pagesLoading).toBe(0);
  });

  test('a jump to a post that fails to load lets the stream carry on', async () => {
    const state = stream(40, 20);
    failRequests();

    await expect(state.goToNumber(35, true)).resolves.toBeUndefined();
    await settle();

    // Scroll loading waits while the stream is paused for a jump.
    expect(state.paused).toBe(false);
  });

  test('a jump to an index that fails to load lets the stream carry on', async () => {
    const state = stream(40, 20);
    failRequests();

    await expect(state.goToIndex(35, true)).resolves.toBeUndefined();
    await settle();

    expect(state.paused).toBe(false);
  });
});
