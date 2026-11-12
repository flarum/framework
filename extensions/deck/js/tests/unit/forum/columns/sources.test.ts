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
  DISCUSSION_HIDDEN,
  DISCUSSION_RESTORED,
  LIKED,
  POSTED,
  LOCKED,
  POST_RESTORED,
  REMOVED,
  RENAMED,
  REVISED,
  STARTED,
  STICKIED,
  TAGGED,
  membershipEvents,
  subjectPost,
} from '../../../../src/forum/columns/realtimeEvents';
import type { DeckRealtimeEvent } from '../../../../src/forum/columns/DeckColumnType';
import DeckColumns from '../../../../src/forum/extenders/DeckColumns';
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

  function filteredListOf(ids: string[]): DiscussionListSource {
    const source = new DiscussionListSource({ filter: { locked: '' } });
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

  it('asks the server where a restored post’s discussion belongs', () => {
    const source = listOf(['21']);

    expect(source.onRealtime(realtime(POST_RESTORED, postEventPayload('206', '27')))).toBe('check');
  });

  // None of these change a discussion's activity, so a check for activity
  // since the newest on screen would never find it.
  it('re-asks about a discussion restored, re-tagged, locked or stickied', () => {
    const source = filteredListOf(['21']);

    expect(source.onRealtime(realtime(DISCUSSION_RESTORED, { data: discussionData('26') }))).toBe('recheck');
    expect(source.onRealtime(realtime(TAGGED, { data: discussionData('21') }))).toBe('recheck');
    expect(source.onRealtime(realtime(LOCKED, { data: discussionData('21') }))).toBe('recheck');
    expect(source.onRealtime(realtime(STICKIED, { data: discussionData('29') }))).toBe('recheck');
  });

  // Moderators still see a discussion once it's hidden, in an is:hidden column.
  it('re-asks about a discussion hidden, for those who still see it', () => {
    const source = filteredListOf(['21']);

    expect(source.onRealtime(realtime(DISCUSSION_HIDDEN, { data: discussionData('21') }))).toBe('recheck');
  });

  it('re-asks about events extensions register', () => {
    const source = filteredListOf(['21']);

    expect(source.onRealtime(realtime('acmeSolvedEvent', { data: discussionData('21') }))).toBe('updated');

    new DeckColumns().membershipEvents('acmeSolvedEvent').extend(app as any, { name: 'acme-solved', exports: {} });

    try {
      expect(source.onRealtime(realtime('acmeSolvedEvent', { data: discussionData('21') }))).toBe('recheck');
    } finally {
      membershipEvents.delete('acmeSolvedEvent');
    }
  });

  // An unfiltered list leaves out ignored discussions and hidden tags only
  // while no filter is active, so narrowing it to ids would bring them in;
  // and locking or stickying doesn't change what it lists.
  it('leaves an unfiltered column as it was', () => {
    const source = listOf(['21']);

    expect(source.onRealtime(realtime(LOCKED, { data: discussionData('21') }))).toBe('updated');
    expect(source.onRealtime(realtime(STICKIED, { data: discussionData('29') }))).toBeUndefined();
    expect(source.onRealtime(realtime(TAGGED, { data: discussionData('21') }))).toBe('check');
    expect(source.onRealtime(realtime(DISCUSSION_RESTORED, { data: discussionData('26') }))).toBe('check');
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

// A column that shrinks as things leave it (Unread, as discussions are read;
// Flagged, as flags are cleared) has the same things leave the server's list,
// so what it holds is the only reliable measure of where the next page starts.
describe('loading more of a column that has shrunk', () => {
  afterEach(() => jest.restoreAllMocks());

  const page = <T>(items: T[], more = false) => Object.assign([...items], { payload: { links: more ? { next: 'next' } : {} } });
  const tick = () => new Promise((resolve) => setTimeout(resolve, 0));

  it('asks for discussions from where what it holds ends, not from the page number', async () => {
    const source = new DiscussionListSource({});
    (source.state as any).pages = [{ number: 1, items: [discussion('60')], hasNext: true }];
    (source.state as any).extraDiscussions = [discussion('61')];
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(page([]) as any);

    await source.state.loadNext();

    // Two held (one of them moved to the top itself), so the next unseen one is third.
    expect(find.mock.calls[0][1].page.offset).toBe(2);
  });

  it('asks for posts from where what it holds ends, not from the page number', async () => {
    const source = new DiscussionPostsSource('31');
    (source.state as any).pages = [{ number: 1, items: [post('601', '31')], hasNext: true }];
    (source.state as any).extraPosts = [post('602', '31')];
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(page([]) as any);

    await source.state.loadNext();

    expect(find.mock.calls[0][1].page.offset).toBe(2);
  });

  it('leaves the first page’s offset alone', async () => {
    const source = new DiscussionListSource({});
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(page([]) as any);

    await source.state.refresh();

    expect(find.mock.calls[0][1].page.offset).toBe(0);
  });

  it('refills once reading has emptied it while the server has more', async () => {
    const source = new DiscussionListSource({}, (discussion) => discussion.title() !== 'Read');
    const read = discussion('62');
    (source.state as any).pages = [{ number: 1, items: [read], hasNext: true }];
    const loadPage = jest.spyOn(source.state as any, 'loadPage').mockResolvedValue(page([discussion('63')]));

    read.pushAttributes({ title: 'Read' });
    source.prune();
    await tick();

    expect(loadPage).toHaveBeenCalled();
    expect(shownIds(source)).toEqual(['63']);
  });

  it('shows nothing once reading has emptied it and the server has no more', async () => {
    const source = new DiscussionListSource({}, (discussion) => discussion.title() !== 'Read');
    const read = discussion('64');
    (source.state as any).pages = [{ number: 1, items: [read], hasNext: false }];
    const loadPage = jest.spyOn(source.state as any, 'loadPage');

    read.pushAttributes({ title: 'Read' });
    source.prune();
    await tick();

    expect(loadPage).not.toHaveBeenCalled();
    expect(shownIds(source)).toEqual([]);
  });
});

// flarum/framework#5178: a Filter column such as is:locked has to gain and
// lose discussions as they're locked, though nothing is posted.
describe('re-asking whether discussions still belong', () => {
  afterEach(() => jest.restoreAllMocks());

  const dated = (id: string, lastPostedAt: string): Discussion => {
    app.store.pushPayload({ data: discussionData(id, { lastPostedAt }) } as any);

    return app.store.getById<Discussion>('discussions', id)!;
  };

  /** A column of discussions on 10, 8 and 6 October, newest first. */
  function filterColumn(sort?: string, hasNext = false): DiscussionListSource {
    const source = new DiscussionListSource({ filter: { locked: '' }, ...(sort ? { sort } : {}) });
    (source.state as any).pages = [
      {
        number: 1,
        items: [dated('60', '2026-10-10T10:00:00Z'), dated('61', '2026-10-08T10:00:00Z'), dated('62', '2026-10-06T10:00:00Z')],
        hasNext,
      },
    ];

    return source;
  }

  const lock = (source: DiscussionListSource, id: string, lastPostedAt = '2026-10-09T10:00:00Z') =>
    source.onRealtime(realtime(LOCKED, { data: discussionData(id, { lastPostedAt }) }));

  const answer = (items: Discussion[]) => jest.spyOn(app.store, 'find').mockResolvedValue(Object.assign([...items], { payload: { links: {} } }) as any);

  it('asks once for every discussion in a burst, the way the column asks', async () => {
    const source = filterColumn();
    const find = answer([]);

    lock(source, '61');
    lock(source, '63');
    lock(source, '61');
    await source.recheck();

    expect(find).toHaveBeenCalledTimes(1);

    const [type, params] = (find as any).mock.calls[0];
    const own = source.state.requestParams();

    expect(type).toBe('discussions');
    expect(params.include).toEqual(asQuery(own).include);
    expect(params.filter).toEqual({ ...own.filter, id: '61,63' });
    expect(params.page).toEqual({ limit: 2, total: 0 });
  });

  it('asks nothing when no event needs it', async () => {
    const source = filterColumn();
    const find = answer([]);

    expect(await source.recheck()).toBe(false);
    expect(find).not.toHaveBeenCalled();
  });

  it('takes out a discussion that no longer matches', async () => {
    const source = filterColumn();
    answer([]);

    lock(source, '61');
    await source.recheck();

    expect(shownIds(source)).toEqual(['60', '62']);
  });

  it('keeps a discussion that still matches', async () => {
    const source = filterColumn();
    answer([app.store.getById<Discussion>('discussions', '61')!]);

    lock(source, '61');
    await source.recheck();

    expect(shownIds(source)).toEqual(['60', '61', '62']);
  });

  it('puts a discussion that starts to match where its last activity places it', async () => {
    const source = filterColumn();

    lock(source, '63', '2026-10-09T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '63')!]);

    expect(await source.recheck()).toBe(false);
    expect(shownIds(source)).toEqual(['60', '63', '61', '62']);
  });

  it('says so when the discussion arrives at the top', async () => {
    const source = filterColumn();

    lock(source, '63', '2026-10-11T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '63')!]);

    expect(await source.recheck()).toBe(true);
    expect(shownIds(source)).toEqual(['63', '60', '61', '62']);
  });

  it('puts an older discussion at the end when the column holds everything', async () => {
    const source = filterColumn();

    lock(source, '63', '2026-10-01T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '63')!]);
    await source.recheck();

    expect(shownIds(source)).toEqual(['60', '61', '62', '63']);
  });

  // Loading more will bring it, at the right place.
  it('leaves an older discussion to a later page when the server has more', async () => {
    const source = filterColumn(undefined, true);

    lock(source, '63', '2026-10-01T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '63')!]);
    await source.recheck();

    expect(shownIds(source)).toEqual(['60', '61', '62']);
  });

  // Where it goes can't be told from what's held, and putting it anywhere
  // would throw off where the next page starts. Refreshing will bring it.
  it('only takes out when the column isn’t sorted by activity', async () => {
    const source = filterColumn('newest');

    lock(source, '63', '2026-10-01T10:00:00Z');
    lock(source, '61');
    answer([app.store.getById<Discussion>('discussions', '60')!, app.store.getById<Discussion>('discussions', '63')!]);

    expect(await source.recheck()).toBe(false);
    expect(shownIds(source)).toEqual(['60', '62']);
  });

  it('counts a discussion that joins at the top as seen, so the next check doesn’t offer it again', async () => {
    const source = filterColumn();
    (source as any).key = new Date('2026-10-10T10:00:00Z');

    lock(source, '63', '2026-10-11T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '63')!]);
    await source.recheck();

    expect((source as any).key).toEqual(new Date('2026-10-11T10:00:00Z'));
  });

  // The API breaks ties by id, highest first.
  it('places a discussion as the API breaks ties on last activity', async () => {
    const source = filterColumn(undefined, true);

    lock(source, '70', '2026-10-06T10:00:00Z');
    answer([app.store.getById<Discussion>('discussions', '70')!]);
    await source.recheck();

    expect(shownIds(source)).toEqual(['60', '61', '70', '62']);
  });

  // flarum/sticky pins stickied discussions above the rest in a list of one
  // tag on the default sort, as on a tag's page.
  describe('in a column of one tag, with stickied discussions pinned', () => {
    function tagColumn(hasNext = false): DiscussionListSource {
      const source = new DiscussionListSource({ filter: { tag: 'news' } });
      app.store.pushPayload({ data: discussionData('80', { lastPostedAt: '2026-09-01T10:00:00Z', isSticky: true }) } as any);
      (source.state as any).pages = [
        {
          number: 1,
          items: [app.store.getById<Discussion>('discussions', '80')!, dated('81', '2026-10-10T10:00:00Z'), dated('82', '2026-10-08T10:00:00Z')],
          hasNext,
        },
      ];

      return source;
    }

    const retag = (source: DiscussionListSource, id: string, attributes: Record<string, unknown>) => {
      source.onRealtime(realtime(TAGGED, { data: discussionData(id, attributes) }));
      answer([app.store.getById<Discussion>('discussions', id)!]);
    };

    it('puts a discussion below the stickied ones', async () => {
      const source = tagColumn();

      retag(source, '83', { lastPostedAt: '2026-10-11T10:00:00Z' });

      expect(await source.recheck()).toBe(false);
      expect(shownIds(source)).toEqual(['80', '83', '81', '82']);
    });

    it('puts a stickied discussion among the stickied ones, however old', async () => {
      const source = tagColumn(true);

      retag(source, '84', { lastPostedAt: '2026-08-01T10:00:00Z', isSticky: true });
      await source.recheck();

      expect(shownIds(source)).toEqual(['80', '84', '81', '82']);
    });
  });

  it('doesn’t add to a column that hasn’t loaded', async () => {
    const source = new DiscussionListSource({ filter: { locked: '' } });

    lock(source, '63');
    answer([app.store.getById<Discussion>('discussions', '63')!]);

    expect(await source.recheck()).toBe(false);
    expect(source.state.hasItems()).toBe(false);
  });

  it('asks again next time when the request fails', async () => {
    const source = filterColumn();
    jest.spyOn(app.store, 'find').mockRejectedValueOnce(new Error('offline'));

    lock(source, '61');
    await expect(source.recheck()).rejects.toThrow('offline');

    const find = answer([]);
    await source.recheck();

    expect((find as any).mock.calls[0][1].filter.id).toBe('61');
    expect(shownIds(source)).toEqual(['60', '62']);
  });

  it('splits a long burst into requests the API accepts', async () => {
    const source = filterColumn();
    const find = answer([]);

    for (let i = 0; i < 60; i++) lock(source, String(100 + i));
    await source.recheck();

    expect(find).toHaveBeenCalledTimes(2);
    expect((find as any).mock.calls.map((call: any) => call[1].page.limit)).toEqual([50, 10]);
  });

  it('leaves likes, edits and renames to update in place, without a request', () => {
    const source = filterColumn();

    expect(source.onRealtime(realtime(RENAMED, { data: discussionData('60') }))).toBe('updated');
    expect(source.onRealtime(realtime(LIKED, postEventPayload('601', '60')))).toBe('updated');
    expect(source.onRealtime(realtime(REVISED, postEventPayload('602', '60')))).toBe('updated');
  });
});
