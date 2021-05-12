/// <reference types="mithril" />
import DefaultResolver from '../../common/resolvers/DefaultResolver';
/**
 * A custom route resolver for DiscussionPage that generates the same key to all posts
 * on the same discussion. It triggers a scroll when going from one post to another
 * in the same discussion.
 */
export default class DiscussionPageResolver extends DefaultResolver {
    static scrollToPostNumber: string | null;
    /**
     * Remove optional parts of a discussion's slug to keep the substring
     * that bijectively maps to a discussion object. By default this just
     * extracts the numerical ID from the slug. If a custom discussion
     * slugging driver is used, this may need to be overriden.
     * @param slug
     */
    canonicalizeDiscussionSlug(slug: string | undefined): string | undefined;
    /**
     * @inheritdoc
     */
    makeKey(): string;
    onmatch(args: any, requestedPath: any, route: any): import("mithril").Component<{}, {}>;
    render(vnode: any): any[];
}
