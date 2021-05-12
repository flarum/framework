/**
 * The `DiscussionListItem` component shows a single discussion in the
 * discussion list.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `params`
 */
export default class DiscussionListItem extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Set up a subtree retainer so that the discussion will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    subtree: SubtreeRetainer | undefined;
    elementAttrs(): {
        className: string;
    };
    highlightRegExp: RegExp | undefined;
    /**
     * Determine whether or not the discussion is currently being viewed.
     *
     * @return {Boolean}
     */
    active(): boolean;
    /**
     * Determine whether or not information about who started the discussion
     * should be displayed instead of information about the most recent reply to
     * the discussion.
     *
     * @return {Boolean}
     */
    showFirstPost(): boolean;
    /**
     * Determine whether or not the number of replies should be shown instead of
     * the number of unread posts.
     *
     * @return {Boolean}
     */
    showRepliesCount(): boolean;
    /**
     * Mark the discussion as read.
     */
    markAsRead(): void;
    /**
     * Build an item list of info for a discussion listing. By default this is
     * just the first/last post indicator.
     *
     * @return {ItemList}
     */
    infoItems(): ItemList;
}
import Component from "../../common/Component";
import SubtreeRetainer from "../../common/utils/SubtreeRetainer";
import ItemList from "../../common/utils/ItemList";
