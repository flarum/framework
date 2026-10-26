import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import app from 'flarum/forum/app';
import PostListSource from './PostListSource';
import { relatedId } from './realtimeEvents';

/**
 * Posts by members of a group, via Deck's `authorGroup` filter. Realtime posts
 * are placed from the author's groups when the payload carries them, and left
 * to the server when it doesn't.
 */
export default class GroupPostsSource extends PostListSource {
  constructor(protected groupId: string) {
    super({ filter: { authorGroup: groupId } });
  }

  protected matches(post: Post): boolean | undefined {
    return this.inGroup(relatedId(post, 'user'));
  }

  protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined {
    return this.inGroup(relatedId(discussion, 'user'));
  }

  /** Decided from the author's groups when the store has them, otherwise by the server. */
  protected inGroup(userId: string | null): boolean | undefined {
    const user = userId ? app.store.getById<User>('users', userId) : null;
    const groups = user ? user.groups() : null;

    if (!Array.isArray(groups)) return undefined;

    return groups.some((group) => group?.id() === this.groupId);
  }
}
