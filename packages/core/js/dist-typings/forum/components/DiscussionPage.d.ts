/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage extends Page {
    /**
     * The discussion that is being viewed.
     *
     * @type {Discussion}
     */
    discussion: any;
    /**
     * The number of the first post that is currently visible in the viewport.
     *
     * @type {number}
     */
    near: number | undefined;
    /**
     * Load the discussion from the API or use the preloaded one.
     */
    load(): void;
    /**
     * Get the parameters that should be passed in the API request to get the
     * discussion.
     *
     * @return {Object}
     */
    requestParams(): Object;
    /**
     * Initialize the component to display the given discussion.
     *
     * @param {Discussion} discussion
     */
    show(discussion: any): void;
    stream: PostStreamState | undefined;
    /**
     * Build an item list for the contents of the sidebar.
     *
     * @return {ItemList}
     */
    sidebarItems(): ItemList;
    /**
     * When the posts that are visible in the post stream change (i.e. the user
     * scrolls up or down), then we update the URL and mark the posts as read.
     *
     * @param {Integer} startNumber
     * @param {Integer} endNumber
     */
    positionChanged(startNumber: any, endNumber: any): void;
}
import Page from "../../common/components/Page";
import PostStreamState from "../states/PostStreamState";
import ItemList from "../../common/utils/ItemList";
