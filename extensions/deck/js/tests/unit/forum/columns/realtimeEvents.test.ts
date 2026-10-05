import app from 'flarum/forum/app';
import type Post from 'flarum/common/models/Post';
import { POSTED, STARTED, relatedId, subjectPost } from '../../../../src/forum/columns/realtimeEvents';
import { boot, discussionData, postData } from '../helpers';

beforeAll(() => boot());

describe('subjectPost', () => {
  // Realtime sends post events as the post's discussion, which includes the
  // discussion's own first and last posts: the subject is the one appended last.
  it('is the post appended last to a post event', () => {
    const payload = { data: discussionData('1'), included: [postData('1', '1'), postData('7', '1')] };

    app.store.pushPayload(payload as any);

    expect(subjectPost(POSTED, payload)?.id()).toBe('7');
  });

  it('is nothing for an event that is not about a post', () => {
    const payload = { data: discussionData('2'), included: [postData('8', '2')] };

    app.store.pushPayload(payload as any);

    expect(subjectPost(STARTED, payload)).toBeNull();
  });

  it('is nothing when the payload carries no post', () => {
    expect(subjectPost(POSTED, { data: discussionData('3') })).toBeNull();
  });
});

describe('relatedId', () => {
  it('reads a relationship without its model being loaded', () => {
    app.store.pushPayload({ data: postData('9', '42', '99') } as any);
    const post = app.store.getById<Post>('posts', '9')!;

    expect(app.store.getById('discussions', '42')).toBeUndefined();
    expect(relatedId(post, 'discussion')).toBe('42');
    expect(relatedId(post, 'user')).toBe('99');
    expect(relatedId(post, 'editedUser')).toBeNull();
  });
});
