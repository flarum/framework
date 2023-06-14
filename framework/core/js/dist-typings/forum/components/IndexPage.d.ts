import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';
export interface IIndexPageAttrs extends IPageAttrs {
}
/**
 * The `IndexPage` component displays the index page, including the welcome
 * hero, the sidebar, and the discussion list.
 */
export default class IndexPage<CustomAttrs extends IIndexPageAttrs = IIndexPageAttrs, CustomState = {}> extends Page<CustomAttrs, CustomState> {
    static providesInitialSearch: boolean;
    lastDiscussion?: Discussion;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    setTitle(): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onbeforeremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Get the component to display as the hero.
     */
    hero(): JSX.Element;
    /**
     * Build an item list for the sidebar of the index page. By default this is a
     * "New Discussion" button, and then a DropdownSelect component containing a
     * list of navigation items.
     */
    sidebarItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the navigation in the sidebar of the index page. By
     * default this is just the 'All Discussions' link.
     */
    navItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the part of the toolbar which is concerned with how
     * the results are displayed. By default this is just a select box to change
     * the way discussions are sorted.
     */
    viewItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the part of the toolbar which is about taking action
     * on the results. By default this is just a "mark all as read" button.
     */
    actionItems(): ItemList<Mithril.Children>;
    /**
     * Open the composer for a new discussion or prompt the user to login.
     */
    newDiscussionAction(): Promise<unknown>;
    /**
     * Mark all discussions as read.
     */
    markAllAsRead(): void;
}
