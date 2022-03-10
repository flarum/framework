/**
 * The `IndexPage` component displays the index page, including the welcome
 * hero, the sidebar, and the discussion list.
 */
export default class IndexPage extends Page<import("../../common/components/Page").IPageAttrs> {
    static providesInitialSearch: boolean;
    constructor();
    lastDiscussion: any;
    setTitle(): void;
    /**
     * Get the component to display as the hero.
     *
     * @return {import('mithril').Children}
     */
    hero(): import('mithril').Children;
    /**
     * Build an item list for the sidebar of the index page. By default this is a
     * "New Discussion" button, and then a DropdownSelect component containing a
     * list of navigation items.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    sidebarItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the navigation in the sidebar of the index page. By
     * default this is just the 'All Discussions' link.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    navItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the part of the toolbar which is concerned with how
     * the results are displayed. By default this is just a select box to change
     * the way discussions are sorted.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    viewItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the part of the toolbar which is about taking action
     * on the results. By default this is just a "mark all as read" button.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    actionItems(): ItemList<import('mithril').Children>;
    /**
     * Open the composer for a new discussion or prompt the user to login.
     *
     * @return {Promise<void>}
     */
    newDiscussionAction(): Promise<void>;
    /**
     * Mark all discussions as read.
     */
    markAllAsRead(): void;
}
import Page from "../../common/components/Page";
import ItemList from "../../common/utils/ItemList";
