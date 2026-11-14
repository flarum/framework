import DiscussionListSource from './DiscussionListSource';
/** The member's current slug, for the `author` filters, which take slugs. */
export declare function resolveAuthorSlug(userId: string): Promise<string>;
/**
 * Discussions started by one member. The column stores the member's id, not
 * their username, and looks up their current slug when it loads, so renaming
 * the member (or changing the forum's slug driver) doesn't break it.
 */
export default class AuthorDiscussionsSource extends DiscussionListSource {
    protected userId: string;
    constructor(userId: string);
    protected prepare(): Promise<unknown>;
}
