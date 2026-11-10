import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import { jest } from '@jest/globals';
import deckColumnTypes from 'ext:flarum/deck/forum/columns/deckColumnTypes';
import { POSTED } from 'ext:flarum/deck/forum/columns/realtimeEvents';
import Flag from '../../../src/forum/models/Flag';
import FlaggedPostsSource from '../../../src/forum/FlaggedPostsSource';
import extendDeck from '../../../src/forum/extendDeck';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  new Extend.Store().add('flags', Flag).extend(app, { name: 'flarum-flags', exports: {} });
  new Extend.Model(Post).hasMany('flags').extend(app, { name: 'flarum-flags', exports: {} });
  extendDeck().forEach((extender) => extender.extend(app, { name: 'flarum-flags', exports: {} }));
});

afterEach(() => jest.restoreAllMocks());

/** A post as the flagged filter returns it, with its open flags. */
function flagged(id: string, flagIds: string[] = [`f${id}`]): Post {
  app.store.pushPayload({
    data: {
      type: 'posts',
      id,
      attributes: { number: Number(id), contentType: 'comment', createdAt: '2026-10-01T10:00:00+00:00' },
      relationships: {
        discussion: { data: { type: 'discussions', id: '1' } },
        flags: { data: flagIds.map((flagId) => ({ type: 'flags', id: flagId })) },
      },
    },
    included: flagIds.map((flagId) => ({ type: 'flags', id: flagId, attributes: { type: 'user', reason: 'spam' } })),
  } as any);

  return app.store.getById<Post>('posts', id)!;
}

const page = <T>(items: T[]) => Object.assign([...items], { payload: { links: {} } });

function columnOf(...posts: Post[]): FlaggedPostsSource {
  const source = new FlaggedPostsSource();
  (source.state as any).pages = [{ number: 1, items: posts }];

  return source;
}

const shown = (source: FlaggedPostsSource) => source.state.getPages().flatMap((page) => page.items.map((post: any) => post.id()));
const event = (name: string, post: Post | null = null) => ({ name, payload: {}, model: null, discussion: null, post });

describe('FlaggedPostsSource', () => {
  it('asks for flagged posts in the order they were flagged', () => {
    const params = new FlaggedPostsSource().state.requestParams();

    expect(params.filter).toMatchObject({ flagged: true });
    expect(params).not.toHaveProperty('sort');
    // As the discussion page asks for posts: the endpoint's defaults, so each
    // comes with its flags, discussion and whatever else extensions add.
    expect(params).not.toHaveProperty('include');
  });

  it('checks with the server when a flag is raised or dismissed', () => {
    expect(columnOf(flagged('1')).onRealtime(event('flagged'))).toBe('check');
  });

  it('ignores new replies, which have no flags yet', () => {
    expect(columnOf(flagged('1')).onRealtime(event(POSTED, flagged('9', [])))).toBeUndefined();
  });

  // Dismissing from the column's own flag bar takes the post's flags away in the store.
  it('lets go of a post once its flags are dismissed', () => {
    const dismissed = flagged('2');
    const source = columnOf(flagged('1'), dismissed);

    delete (dismissed.data.relationships as any).flags;
    source.prune?.();

    expect(shown(source)).toEqual(['1']);
  });

  // A post can be flagged long after it was written, so newer ids say nothing.
  it('puts newly flagged posts in by reloading the queue', async () => {
    const source = columnOf(flagged('5'));
    jest.spyOn(source.state as any, 'loadPage').mockResolvedValue(page([flagged('3'), flagged('5')]));

    expect(await source.applyNew()).toBe(1);
    expect(shown(source)).toEqual(['3', '5']);
  });

  it('counts newly flagged posts for the "new" pill', async () => {
    const source = columnOf(flagged('5'));
    jest.spyOn(app.store, 'find').mockResolvedValue([flagged('3'), flagged('5')] as any);

    expect(await source.checkForNew()).toBe(1);
  });
});

describe('the Flagged posts column', () => {
  it('is only offered to members who can see flags', () => {
    const type = deckColumnTypes.get('flarum-flags.flagged');

    app.forum.pushAttributes({ canViewFlags: false });
    expect(type.isAvailable()).toBe(false);

    app.forum.pushAttributes({ canViewFlags: true });
    expect(type.isAvailable()).toBe(true);
  });
});
