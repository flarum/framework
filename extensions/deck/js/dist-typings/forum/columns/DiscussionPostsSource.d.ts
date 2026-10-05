/// <reference types="mithril" />
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import PostListSource from './PostListSource';
/**
 * The newest replies in one discussion. Core's PostStream is bound to window
 * scrolling and can't live in a column, so this is a newest-first post list
 * with a link through to the real discussion page.
 */
export default class DiscussionPostsSource extends PostListSource {
    protected discussionId: string;
    constructor(discussionId: string);
    protected matches(post: Post): boolean;
    /** A new discussion is never this one. */
    protected mayMatchNewDiscussion(): boolean;
    discussion(): Discussion | undefined;
    protected prepare(): Promise<unknown>;
    view(): JSX.Element;
}
