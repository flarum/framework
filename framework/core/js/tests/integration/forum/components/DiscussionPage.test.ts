import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import DiscussionPage from '../../../../src/forum/components/DiscussionPage';
import Discussion from '../../../../src/common/models/Discussion';
import Post from '../../../../src/common/models/Post';
import mq from 'mithril-query';
import m from 'mithril';

/**
 * Clicking into a discussion must not serialize its network round-trips.
 * The near-window of posts is derivable from the route alone, so it is
 * requested in PARALLEL with the discussion — not after it. These tests pin
 * that contract; the serial flow is kept only when the discussion can't be
 * identified client-side.
 */

const makePostData = (id: number, number: number, discussionId = '476') => ({
  type: 'posts',
  id: String(id),
  attributes: {
    number,
    contentType: 'comment',
    contentHtml: `<p>Post ${number}</p>`,
    createdAt: '2024-01-01T00:00:00Z',
    // The stream treats a post without canEdit as a partial record (e.g. from
    // a search result) and reloads it — real post payloads always carry it.
    canEdit: false,
  },
  relationships: {
    discussion: { data: { type: 'discussions', id: discussionId } },
    user: { data: { type: 'users', id: '1' } },
  },
});

const discussionData = (postIds: number[], slug = '476-test-discussion') => ({
  type: 'discussions',
  id: '476',
  attributes: {
    title: 'Test discussion',
    slug,
    commentCount: postIds.length,
    createdAt: '2024-01-01T00:00:00Z',
  },
  relationships: {
    posts: { data: postIds.map((id) => ({ type: 'posts', id: String(id) })) },
  },
});

describe('DiscussionPage', () => {
  const originalRouteParam = m.route.param;
  const originalFind = app.store.find;

  let routeParams: Record<string, string | undefined>;
  let findCalls: any[][];
  let pendingFinds: { args: any[]; resolve: (value: any) => void; reject: (error: any) => void }[];

  beforeAll(() => {
    bootstrapForum();
    app.boot();

  });

  beforeEach(() => {
    routeParams = {};
    findCalls = [];
    pendingFinds = [];

    // The pane helper is created by ForumApplication when mounting the real
    // app; the pane component's lifecycle hooks expect it to exist.
    // @ts-ignore
    app.pane = app.pane || { enable() {}, disable() {}, hide() {}, show() {}, onmouseleave() {}, togglePinned() {} };

    // @ts-ignore
    m.route.param = (key?: string) => (key ? routeParams[key] : routeParams);

    // Intercept store.find: record the call, return a promise we resolve by hand.
    // @ts-ignore
    app.store.find = (...args: any[]) => {
      findCalls.push(args);
      return new Promise((resolve, reject) => {
        pendingFinds.push({ args, resolve, reject });
      });
    };
  });

  afterEach(() => {
    m.route.param = originalRouteParam;
    app.store.find = originalFind;
  });

  const flushAsync = () => new Promise((resolve) => setTimeout(resolve, 0));

  const findsFor = (type: string) => findCalls.filter((args) => args[0] === type);

  /** Simulate the discussion being known from the discussion list. */
  const seedDiscussionFromList = () => {
    app.store.pushPayload({
      data: {
        type: 'discussions',
        id: '476',
        attributes: { title: 'Test discussion', slug: '476-test-discussion', commentCount: 3 },
      },
    });
  };

  const mountPage = () => mq(DiscussionPage as any, { id: '476-test-discussion' });

  /** Resolve the pending discussion request with a full show document. */
  const resolveDiscussion = (postIds: number[]) => {
    const pending = pendingFinds.find((p) => p.args[0] === 'discussions');
    const document = { data: discussionData(postIds), included: postIds.map((id, i) => makePostData(id, i + 1)) };
    const models = app.store.pushPayload<Discussion>(document as any);
    pending!.resolve(models);
  };

  /** Resolve the pending posts request with the near-window. */
  const resolvePosts = (postIds: number[]) => {
    const pending = pendingFinds.find((p) => p.args[0] === 'posts');
    const document = { data: postIds.map((id, i) => makePostData(id, i + 1)) };
    const models = app.store.pushPayload<Post[]>(document as any);
    pending!.resolve(models);
  };

  test('keeps the page structure while the discussion loads instead of blanking', () => {
    // Navigating between discussions remounts the page. Rendering a bare
    // loading indicator during the fetch blanks the whole layout — including
    // the pinned discussion list pane, whose contents are already cached and
    // need no network. The shell must stay; only the main area may spin.
    seedDiscussionFromList();
    routeParams = { id: '476-test-discussion' };

    const page = mountPage();

    // Nothing resolved yet: the page is loading. The pane slot must exist
    // (its contents render whenever the list state has items), and the
    // spinner must be inside the main area, not the whole page.
    expect(page).toHaveElement('.DiscussionPage');
    expect(page).toHaveElement('.Page-pane');
    expect(page).toHaveElement('#page-main .LoadingIndicator');
  });

  test('requests the discussion and the post window in parallel when the discussion is known', () => {
    seedDiscussionFromList();
    routeParams = { id: '476-test-discussion' };

    mountPage();

    // Both requests must be in flight immediately — before ANY response and
    // before the async component chunks resolve.
    expect(findsFor('discussions')).toHaveLength(1);
    expect(findsFor('posts')).toHaveLength(1);

    const [, postsParams] = findsFor('posts')[0];
    expect(postsParams).toMatchObject({ filter: { discussion: '476' }, page: { near: 1 } });
  });

  test('the parallel post window honours the near route param', () => {
    seedDiscussionFromList();
    routeParams = { id: '476-test-discussion', near: '5' };

    mountPage();

    const [, postsParams] = findsFor('posts')[0];
    expect(postsParams).toMatchObject({ filter: { discussion: '476' }, page: { near: 5 } });
  });

  test('a single posts request serves the whole page view', async () => {
    seedDiscussionFromList();
    routeParams = { id: '476-test-discussion' };

    mountPage();

    // Posts arrive BEFORE the discussion — the page must handle either order.
    resolvePosts([101, 102, 103]);
    resolveDiscussion([101, 102, 103]);

    await flushAsync();

    // The post stream was satisfied by the parallel window: no second request.
    expect(findsFor('posts')).toHaveLength(1);
    expect(findsFor('discussions')).toHaveLength(1);
  });

  test('falls back to the serial flow when the discussion is not in the store', async () => {
    // The store persists across tests in this suite, so use a discussion no
    // other test has seeded.
    routeParams = { id: '999-unknown-discussion' };

    mq(DiscussionPage as any, { id: '999-unknown-discussion' });

    // Unknown discussion: only the discussion request goes out. The post
    // window cannot be derived (the slug alone does not identify it), so the
    // stream requests posts only after the discussion arrives.
    expect(findsFor('discussions')).toHaveLength(1);
    expect(findsFor('posts')).toHaveLength(0);

    resolveDiscussion([101, 102, 103]);
    await flushAsync();

    expect(findsFor('posts')).toHaveLength(1);
  });

  test('survives an extension override of show() that drops the posts argument', async () => {
    // fof/blog (and likely others) override show() as
    // `override(DiscussionPage.prototype, 'show', (original, discussion) => original(discussion))`,
    // silently dropping the second argument. The prefetched window must not
    // depend on argument threading through show().
    const originalShow = (DiscussionPage.prototype as any).show;
    (DiscussionPage.prototype as any).show = function (discussion: any) {
      return originalShow.call(this, discussion);
    };

    try {
      seedDiscussionFromList();
      routeParams = { id: '476-test-discussion' };

      mountPage();

      resolvePosts([101, 102, 103]);
      resolveDiscussion([101, 102, 103]);

      await flushAsync();

      expect(findsFor('posts')).toHaveLength(1);
    } finally {
      (DiscussionPage.prototype as any).show = originalShow;
    }
  });

  test('does not prefetch posts when navigating to the reply placeholder', () => {
    seedDiscussionFromList();
    routeParams = { id: '476-test-discussion', near: 'reply' };

    mountPage();

    expect(findsFor('discussions')).toHaveLength(1);
    expect(findsFor('posts')).toHaveLength(0);
  });
});
