import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import DiscussionListSource from '../../../../src/forum/columns/DiscussionListSource';
import DiscussionPostsSource from '../../../../src/forum/columns/DiscussionPostsSource';
import PostListSource from '../../../../src/forum/columns/PostListSource';
import PostListState from 'flarum/forum/states/PostListState';
import { extend } from 'flarum/common/extend';
import { asQuery } from '../../../../src/forum/states/deckListStates';
import deckColumnTypes, { registerDefaultColumnTypes } from '../../../../src/forum/columns/deckColumnTypes';
import {
  DISCUSSION_RESTORED,
  LIKED,
  POSTED,
  POST_RESTORED,
  REMOVED,
  RENAMED,
  STARTED,
  TAGGED,
  subjectPost,
} from '../../../../src/forum/columns/realtimeEvents';
import type { DeckRealtimeEvent } from '../../../../src/forum/columns/DeckColumnType';
import { boot, discussionData, postData, postEventPayload } from '../helpers';

beforeAll(() => boot());

/** An event as DeckState builds it from a payload. */
function realtime(name: string, payload: any): DeckRealtimeEvent {
  const pushed = app.store.pushPayload(payload) as any;
  const post = subjectPost(name, payload);
  const discussion = pushed?.data?.type === 'discussions' ? (pushed as Discussion) : post?.discussion() || null;

  return { name, payload, model: pushed, discussion, post };
}

const discussion = (id: string): Discussion => {
  app.store.pushPayload({ data: discussionData(id) } as any);

  return app.store.getById<Discussion>('discussions', id)!;
};

const post = (id: string, discussionId: string): Post => {
  app.store.pushPayload({ data: postData(id, discussionId) } as any);

  return app.store.getById<Post>('posts', id)!;
};

const shownIds = (source: DiscussionListSource | DiscussionPostsSource) =>
  source.state.getPages().flatMap((page) => page.items.map((item: any) => item.id()));

describe('DiscussionListSource', () => {
  function listOf(ids: string[], sort?: string): DiscussionListSource {
    const source = new DiscussionListSource(sort ? { sort } : {});
    (source.state as any).pages = [{ number: 1, items: ids.map(discussion) }];

    return source;
  }

  it('moves a discussion already in the column to the top when someone replies', () => {
    const source = listOf(['21', '22', '23']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('201', '23')))).toBe('inserted');
    expect(shownIds(source)).toEqual(['23', '21', '22']);
  });

  // The member's own reply can change which columns it belongs in (Unread).
  it('asks the server about the member’s own replies', () => {
    const source = listOf(['21', '22']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('202', '22', '1')))).toBe('check');
    expect(shownIds(source)).toEqual(['21', '22']);
  });

  it('asks the server about activity in a discussion it doesn’t hold', () => {
    const source = listOf(['21']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('203', '24')))).toBe('check');
    expect(source.onRealtime(realtime(STARTED, { data: discussionData('25') }))).toBe('check');
  });

  it('leaves the order alone when not sorted by activity', () => {
    const source = listOf(['21', '22'], 'newest');

    expect(source.onRealtime(realtime(POSTED, postEventPayload('204', '22')))).toBe('check');
    expect(shownIds(source)).toEqual(['21', '22']);
  });

  it('takes out a removed discussion', () => {
    const source = listOf(['21', '22']);

    expect(source.onRealtime(realtime(REMOVED, { data: { type: 'discussions', id: '22' } }))).toBe('updated');
    expect(shownIds(source)).toEqual(['21']);
  });

  it('leaves a removed post to the discussion', () => {
    const source = listOf(['21']);
    const event = { ...realtime(REMOVED, { data: { type: 'posts', id: '205' } }), discussion: discussion('21') };

    expect(source.onRealtime(event)).toBeUndefined();
    expect(shownIds(source)).toEqual(['21']);
  });

  it('asks the server where restored and re-tagged discussions belong', () => {
    const source = listOf(['21']);

    expect(source.onRealtime(realtime(DISCUSSION_RESTORED, { data: discussionData('26') }))).toBe('check');
    expect(source.onRealtime(realtime(POST_RESTORED, postEventPayload('206', '27')))).toBe('check');
    expect(source.onRealtime(realtime(TAGGED, { data: discussionData('21') }))).toBe('check');
  });

  it('redraws for changes to discussions it shows, and ignores the rest', () => {
    const source = listOf(['21']);

    expect(source.onRealtime(realtime(RENAMED, { data: discussionData('21') }))).toBe('updated');
    expect(source.onRealtime(realtime(RENAMED, { data: discussionData('28') }))).toBeUndefined();
  });
});

describe('DiscussionPostsSource', () => {
  function postsIn(discussionId: string, ids: string[]): DiscussionPostsSource {
    discussion(discussionId);
    const source = new DiscussionPostsSource(discussionId);
    (source.state as any).pages = [{ number: 1, items: ids.map((id) => post(id, discussionId)) }];

    return source;
  }

  it('inserts a reply to its discussion straight from the payload', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('302', '31')))).toBe('inserted');
    expect(shownIds(source)).toEqual(['302', '301']);
  });

  it('ignores replies elsewhere and new discussions', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('303', '32')))).toBeUndefined();
    expect(source.onRealtime(realtime(STARTED, { data: discussionData('33') }))).toBeUndefined();
    expect(shownIds(source)).toEqual(['301']);
  });

  it('ignores event posts, such as a rename', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('304', '31', '2', 'discussionRenamed')))).toBeUndefined();
  });

  it('does not insert a post it already shows', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(POSTED, postEventPayload('301', '31')))).toBeUndefined();
    expect(shownIds(source)).toEqual(['301']);
  });

  it('takes out a removed post', () => {
    const source = postsIn('31', ['301', '305']);

    expect(source.onRealtime(realtime(REMOVED, { data: { type: 'posts', id: '305' } }))).toBe('updated');
    expect(shownIds(source)).toEqual(['301']);
  });

  it('redraws for a like on a post it shows', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(LIKED, postEventPayload('301', '31')))).toBe('updated');
  });

  it('asks the server where a restored post of its discussion goes', () => {
    const source = postsIn('31', ['301']);

    expect(source.onRealtime(realtime(POST_RESTORED, postEventPayload('306', '31')))).toBe('check');
    expect(source.onRealtime(realtime(POST_RESTORED, postEventPayload('307', '34')))).toBeUndefined();
  });
});

describe('keeping columns current', () => {
  /** A page as the API would return it. */
  const page = <T>(items: T[]) => Object.assign([...items], { payload: { links: {} } });

  // Reading a discussion sends no realtime event, so a column has to notice by itself.
  it('drops discussions that no longer belong, judged from the store', () => {
    const source = new DiscussionListSource({}, (discussion) => discussion.title() !== 'Read');
    const read = discussion('41');
    (source.state as any).pages = [{ number: 1, items: [discussion('40'), read] }];
    (source.state as any).extraDiscussions = [discussion('42')];

    read.pushAttributes({ title: 'Read' });
    source.prune?.();

    expect(shownIds(source)).toEqual(['42', '40']);
  });

  it('takes the server’s word on refresh, dropping what it moved to the top itself', async () => {
    const source = new DiscussionListSource({});
    (source.state as any).pages = [{ number: 1, items: [discussion('40')] }];
    (source.state as any).extraDiscussions = [discussion('43')];
    jest.spyOn(source.state as any, 'loadPage').mockResolvedValue(page([discussion('40')]));

    await source.showNew();

    expect(shownIds(source)).toEqual(['40']);
  });

  it('keeps what it has when a refresh fails', async () => {
    const source = new DiscussionListSource({});
    (source.state as any).pages = [{ number: 1, items: [discussion('40')] }];
    (source.state as any).extraDiscussions = [discussion('43')];
    jest.spyOn(source.state as any, 'loadPage').mockRejectedValue(new Error('offline'));

    await source.showNew();

    expect(shownIds(source)).toEqual(['43', '40']);
  });

  it('shows a reply it inserted only once after a refresh', async () => {
    const source = new DiscussionPostsSource('31');
    (source.state as any).pages = [{ number: 1, items: [post('401', '31')] }];
    (source.state as any).extraPosts = [post('402', '31')];
    jest.spyOn(source.state as any, 'loadPage').mockResolvedValue(page([post('402', '31'), post('401', '31')]));

    await source.showNew();

    expect(shownIds(source)).toEqual(['402', '401']);
  });
});

describe('the Unread column', () => {
  beforeAll(() => {
    if (!deckColumnTypes.has('unread')) registerDefaultColumnTypes();
  });

  it('lets go of a discussion once it has been read', () => {
    const source = deckColumnTypes.get('unread').createSource({ id: 'u', type: 'unread', width: 280, params: {} }) as DiscussionListSource;
    app.store.pushPayload({
      data: discussionData('50', { lastPostNumber: 5, lastReadPostNumber: 2, commentCount: 5, lastPostedAt: '2030-01-01T00:00:00+00:00' }),
    } as any);
    const unread = app.store.getById<Discussion>('discussions', '50')!;
    (source.state as any).pages = [{ number: 1, items: [unread] }];

    source.prune?.();
    expect(shownIds(source)).toEqual(['50']);

    unread.pushAttributes({ lastReadPostNumber: 5 });
    source.prune?.();
    expect(shownIds(source)).toEqual([]);
  });
});

describe('post columns', () => {
  // The discussion page asks for posts without an include, so it gets the
  // endpoint's defaults, which extensions add to. Columns must too: naming any
  // include, even one an extension adds to PostListState's (as fof/geoip adds
  // ipInfo), turns every default off, and posts arrive without their discussion.
  it('ask for posts as the discussion page does, whatever extensions add to the list', () => {
    const restore = PostListState.prototype.requestParams;
    extend(PostListState.prototype, 'requestParams', function (params: any) {
      params.include = [...(params.include || []), 'ipInfo'];
    });

    try {
      expect(new DiscussionPostsSource('31').state.requestParams()).not.toHaveProperty('include');
    } finally {
      PostListState.prototype.requestParams = restore;
    }
  });

  it('ask for the newest posts first', () => {
    const source = new DiscussionPostsSource('31');

    expect(source.state.requestParams().sort).toBe('-createdAt');
  });

  // e.g. flagged posts, which a filter orders by when they were flagged.
  it('leave the order to the server when told to', () => {
    const source = new PostListSource({ filter: { flagged: true }, sort: '' });

    expect(source.state.requestParams()).not.toHaveProperty('sort');
  });
});

// Checks for new items ask exactly what the column itself does, so the posts
// and discussions they bring in have everything the column's own do.
describe('checking for new items', () => {
  afterEach(() => jest.restoreAllMocks());

  const findCall = () => (app.store.find as any).mock.calls[0];

  it('asks for posts the way the column does', async () => {
    const source = new DiscussionPostsSource('31');
    const find = jest.spyOn(app.store, 'find').mockResolvedValue([] as any);

    await source.checkForNew();

    const [type, params] = findCall();
    const own = source.state.requestParams();

    expect(type).toBe('posts');
    // Post columns name no includes. A key left undefined is sent as a bare
    // `?include`, which asks for nothing: posts would arrive without their
    // discussion, and rendering them throws.
    expect(own).not.toHaveProperty('include');
    expect(params).not.toHaveProperty('include');
    expect(params.filter).toMatchObject(own.filter as object);
    find.mockRestore();
  });

  it('asks for discussions the way the column does', async () => {
    const source = new DiscussionListSource({});
    (source as any).key = new Date('2026-10-01T10:00:00Z');
    const find = jest.spyOn(app.store, 'find').mockResolvedValue([] as any);

    await source.checkForNew();

    const [type, params] = findCall();

    expect(type).toBe('discussions');
    expect(params.include).toEqual(asQuery(source.state.requestParams()).include);
    expect(params.filter).toMatchObject({ lastPostedAfter: '2026-10-01T10:00:00.000Z' });
    find.mockRestore();
  });
});
