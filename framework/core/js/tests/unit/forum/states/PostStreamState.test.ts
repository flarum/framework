import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import PostStreamState from '../../../../src/forum/states/PostStreamState';
import DiscussionPage from '../../../../src/forum/components/DiscussionPage';
import Discussion from '../../../../src/common/models/Discussion';
import Post from '../../../../src/common/models/Post';

beforeAll(() => bootstrapForum());

let nextId = 0;

/**
 * Push a discussion whose `posts` relationship lists `postCount` posts, and
 * load the posts at `loadedOffsets` (0-based positions in that list) into the
 * store as real posts. Mirrors the shape the server embeds in the preloaded
 * discussion document on a cold page load.
 *
 * Every call uses a fresh, globally-unique id range so that posts left in the
 * shared `app.store` by earlier tests can never leak into a later one — the
 * store accumulates across tests in a file and there is no reset helper.
 *
 * Returns the discussion plus the list of all post ids (in stream order).
 */
function seedStore(postCount: number, loadedOffsets: number[]): { discussion: Discussion; postIds: string[] } {
  const base = nextId;
  nextId += postCount + 1;

  const discussionId = String(base);
  const postIds = Array.from({ length: postCount }, (_, i) => String(base + 1 + i));

  app.store.pushPayload({
    data: {
      id: discussionId,
      type: 'discussions',
      attributes: { title: 'Discussion title' },
      relationships: {
        posts: { data: postIds.map((id) => ({ id, type: 'posts' })) },
      },
    },
  });

  app.store.pushPayload({
    data: loadedOffsets.map((offset) => ({
      id: postIds[offset],
      type: 'posts',
      attributes: {
        number: offset + 1,
        contentType: 'comment',
        canEdit: false,
        createdAt: new Date(),
        contentHtml: `<p>Post ${postIds[offset]}</p>`,
      },
      relationships: { discussion: { data: { id: discussionId, type: 'discussions' } } },
    })),
  });

  return { discussion: app.store.getById<Discussion>('discussions', discussionId)!, postIds };
}

function postsFor(ids: string[]): Post[] {
  return ids.map((id) => app.store.getById<Post>('posts', id)!);
}

// preloadedNearPage only reads the store + discussion.postIds() — no component
// state — so we can call it off a bare prototype instance.
function nearPage(discussion: Discussion): Post[] {
  const page = Object.create(DiscussionPage.prototype) as DiscussionPage;
  return page.preloadedNearPage(discussion);
}

describe('PostStreamState', () => {
  // Regression tests for #4703 / #4702 (and the #4137 it must not reintroduce).
  //
  // On a cold discussion-page load the server embeds the page of posts in the
  // preloaded document. Seeding the stream with them avoids a redundant
  // GET /api/posts round-trip (the #4703 LCP fix). But the visible-window math
  // in show() assumes the seed is contiguous in postIds() order; a
  // non-contiguous seed leaves an unloaded id inside the window that renders
  // as a perpetually-loading post — the #4137 failure mode. These lock both
  // behaviours in.
  describe('window seeding', () => {
    test('a contiguous seed produces a window covering exactly those posts, with no gaps', () => {
      const { discussion, postIds } = seedStore(8, [0, 1, 2, 3]);

      const state = new PostStreamState(discussion, postsFor(postIds.slice(0, 4)));

      expect((state as any).visibleStart).toBe(0);
      expect((state as any).visibleEnd).toBe(4);
      expect(state.posts().every((p) => p !== null)).toBe(true);
      expect(state.posts()).toHaveLength(4);
    });

    test('a mid-stream contiguous seed windows around the right offset', () => {
      const { discussion, postIds } = seedStore(20, [9, 10, 11, 12]);

      const state = new PostStreamState(discussion, postsFor(postIds.slice(9, 13)));

      expect((state as any).visibleStart).toBe(9);
      expect((state as any).visibleEnd).toBe(13);
      expect(state.posts().every((p) => p !== null)).toBe(true);
    });

    test('an empty seed leaves the window at the baseline (in-app navigation path is unchanged)', () => {
      const { discussion } = seedStore(4, []);

      const seeded = new PostStreamState(discussion, []);
      const baseline = new PostStreamState(discussion);

      expect((seeded as any).visibleStart).toBe((baseline as any).visibleStart);
      expect((seeded as any).visibleEnd).toBe((baseline as any).visibleEnd);
      expect((seeded as any).visibleStart).toBe(0);
      expect((seeded as any).visibleEnd).toBe(0);
    });
  });

  describe('DiscussionPage.preloadedNearPage (contiguity guard for #4137)', () => {
    test('returns the full run when every post is loaded and contiguous', () => {
      const { discussion, postIds } = seedStore(4, [0, 1, 2, 3]);

      expect(nearPage(discussion).map((p) => p.id())).toEqual(postIds);
    });

    test('drops a non-contiguous stray post so the seed cannot corrupt the window (#4137)', () => {
      // Posts at offsets 0,1 are loaded and contiguous; offset 4 is also loaded
      // (e.g. pulled in by an extension relationship) but 2,3 between them are
      // not. The longest contiguous run is the first two — the stray must be excluded.
      const { discussion, postIds } = seedStore(6, [0, 1, 4]);

      const page = nearPage(discussion);
      expect(page.map((p) => p.id())).toEqual(postIds.slice(0, 2));

      // Feeding that seed into the stream yields a clean, gap-free window.
      const state = new PostStreamState(discussion, page);
      expect(state.posts().every((p) => p !== null)).toBe(true);
    });

    test('picks the longest contiguous run when there are several', () => {
      // Runs: offsets [0,1] and [4,5,6]; the longer (latter) should win.
      const { discussion, postIds } = seedStore(8, [0, 1, 4, 5, 6]);

      expect(nearPage(discussion).map((p) => p.id())).toEqual(postIds.slice(4, 7));
    });

    test('returns an empty array when no posts are loaded (in-app navigation)', () => {
      const { discussion } = seedStore(3, []);

      expect(nearPage(discussion)).toEqual([]);
    });
  });
});
