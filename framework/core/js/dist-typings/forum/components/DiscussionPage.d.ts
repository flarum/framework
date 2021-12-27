import type Mithril from 'mithril';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import PostStreamState from '../states/PostStreamState';
import Discussion from '../../common/models/Discussion';
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
     * List of components shown while the discussion is loading.
     */
    loadingItems(): ItemList<Mithril.Children>;
    /**
     * Function that renders the `sidebarItems` ItemList.
     */
    sidebar(): Mithril.Children;
    /**
     * Renders the discussion's hero.
     */
    hero(): Mithril.Children;
    /**
     * List of items rendered as the main page content.
     */
    pageContent(): ItemList<Mithril.Children>;
    /**
     * List of items rendered inside the main page content container.
     */
    mainContent(): ItemList<Mithril.Children>;
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
    show(discussion: ApiResponseSingle<Discussion>): void;
    /**
     * Build an item list for the contents of the sidebar.
     */
    sidebarItems(): ItemList<Mithril.Children>;
    /**
     * When the posts that are visible in the post stream change (i.e. the user
     * scrolls up or down), then we update the URL and mark the posts as read.
     */
    positionChanged(startNumber: number, endNumber: number): void;
}
