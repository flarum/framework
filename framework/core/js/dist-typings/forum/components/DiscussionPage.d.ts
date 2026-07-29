import type Mithril from 'mithril';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import PostStreamState from '../states/PostStreamState';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';
import { ApiResponseSingle } from '../../common/Store';
export interface IDiscussionPageAttrs extends IPageAttrs {
    id: string;
    near?: number;
}
/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage<CustomAttrs extends IDiscussionPageAttrs = IDiscussionPageAttrs> extends Page<CustomAttrs> {
    protected loading: boolean;
    protected PostStream: any;
    protected PostStreamScrubber: any;
    /**
     * The discussion that is being viewed.
     */
    protected discussion: Discussion | null;
    /**
     * A public API for interacting with the post stream.
     */
    protected stream: PostStreamState | null;
    /**
     * The number of the first post that is currently visible in the viewport.
     */
    protected near: number;
    protected useBrowserScrollRestoration: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    /**
     * Function that renders the `sidebarItems` ItemList.
     */
    sidebar(): Mithril.Children;
    /**
     * Renders the discussion's hero.
     */
    hero(): Mithril.Children;
    /**
     * Load the discussion from the API or use the preloaded one.
     */
    load(): void;
    /**
     * Get the parameters that should be passed in the API request to get the
     * discussion.
     */
    requestParams(): Record<string, unknown>;
    /**
     * Initialize the component to display the given discussion.
     */
    show(discussion: ApiResponseSingle<Discussion>, preloadedPosts?: Post[]): void;
    /**
     * Extract the page of posts that was embedded in the server-preloaded
     * discussion document, so the post stream can render without re-fetching
     * the same posts via the API.
     *
     * On the initial page load, Content\Discussion embeds the `page[near]`
     * window of posts in the preloaded document (it needs them for the noscript
     * content anyway), and `preloadedApiDocument()` has already pushed them into
     * the store by the time this runs. Returns the longest run of posts that is
     * contiguous in stream order: stray posts pulled in by other relationships
     * (e.g. extension includes) must not corrupt the visible window — that
     * non-contiguity is what caused #4137. API show responses (post-#4067)
     * include no posts page, so for in-app navigation this returns an empty
     * array and the stream fetches as before.
     */
    preloadedNearPage(discussion: ApiResponseSingle<Discussion>): Post[];
    /**
     * Build an item list for the contents of the sidebar.
     */
    sidebarItems(): ItemList<Mithril.Children>;
    /**
     * When the posts that are visible in the post stream change (i.e. the user
     * scrolls up or down), then we update the URL and mark the posts as read.
     *
     * URL and history writes are skipped when startNumber hasn't changed from the
     * last known position — this prevents double history churn from the immediate
     * post-scroll emit and the subsequent settle-end reconciliation both resolving
     * to the same post. Read-state saves are intentionally independent of this
     * guard and use their own endNumber condition.
     */
    positionChanged(startNumber: number, endNumber: number): void;
}
