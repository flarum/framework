import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import DiscussionListSource from '../../../../src/forum/columns/DiscussionListSource';
import DiscussionPostsSource from '../../../../src/forum/columns/DiscussionPostsSource';
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
