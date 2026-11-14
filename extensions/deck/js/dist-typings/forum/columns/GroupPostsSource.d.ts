import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import PostListSource from './PostListSource';
/**
 * Posts by members of a group, via Deck's `authorGroup` filter. Realtime posts
 * are placed from the author's groups when the payload carries them, and left
 * to the server when it doesn't.
 */
export default class GroupPostsSource extends PostListSource {
    protected groupId: string;
    constructor(groupId: string);
    protected matches(post: Post): boolean | undefined;
    protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined;
    /** Decided from the author's groups when the store has them, otherwise by the server. */
    protected inGroup(userId: string | null): boolean | undefined;
}
