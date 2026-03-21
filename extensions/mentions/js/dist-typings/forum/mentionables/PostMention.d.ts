import MentionableModel from './MentionableModel';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import type AtMentionFormat from './formats/AtMentionFormat';
export default class PostMention extends MentionableModel<Post, AtMentionFormat> {
    type(): string;
    /**
     * If the user is replying to a discussion, or if they are editing a
     * post, then we can suggest other posts in the discussion to mention.
     * We will add the 5 most recent comments in the discussion which
     * match any username characters that have been typed.
     */
    initialResults(): Post[];
    /**
     * Generates the syntax for mentioning of a post. Also cleans up the display name.
     *
     * @example <caption>Post mention</caption>
     * // '@"User"#p13'
     * // @"Display name"#pPostID
     * forPostMention(user, 13) // User display name is 'User', post ID is 13
     */
    replacement(post: Post): string;
    suggestion(model: Post, typed: string): Mithril.Children;
    matches(model: Post, typed: string): boolean;
    maxStoreMatchedResults(): number;
    /**
     * Post mention suggestions are only offered from current discussion posts.
     */
    search(typed: string): Promise<Post[]>;
    enabled(): boolean;
}
