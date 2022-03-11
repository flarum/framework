import type Mithril from 'mithril';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import DiscussionPage, { IDiscussionPageAttrs } from '../components/DiscussionPage';
/**
 * A custom route resolver for DiscussionPage that generates the same key to all posts
 * on the same discussion. It triggers a scroll when going from one post to another
 * in the same discussion.
 */
export default class DiscussionPageResolver<Attrs extends IDiscussionPageAttrs = IDiscussionPageAttrs, RouteArgs extends Record<string, unknown> = {}> extends DefaultResolver<Attrs, DiscussionPage<Attrs>, RouteArgs> {
    static scrollToPostNumber: number | null;
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
    onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string): new () => DiscussionPage<Attrs>;
    render(vnode: Mithril.Vnode<Attrs, DiscussionPage<Attrs>>): Mithril.Children;
}
