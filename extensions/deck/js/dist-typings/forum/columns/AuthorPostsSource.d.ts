import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import PostListSource from './PostListSource';
/** A member's newest comments anywhere, by id so renames don't break it. */
export default class AuthorPostsSource extends PostListSource {
    protected userId: string;
    constructor(userId: string);
    protected matches(post: Post): boolean | undefined;
    protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined;
    protected prepare(): Promise<unknown>;
}
