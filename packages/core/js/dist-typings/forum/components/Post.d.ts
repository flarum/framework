/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 *
 * ### Attrs
 *
 * - `post`
 *
 * @abstract
 */
export default class Post extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    /**
     * May be set by subclasses.
     */
    loading: boolean | undefined;
    /**
     * Set up a subtree retainer so that the post will not be redrawn
     * unless new data comes in.
     *
     * @type {SubtreeRetainer}
     */
    subtree: SubtreeRetainer | undefined;
    /**
     * Get attributes for the post element.
     *
     * @return {Record<string, unknown>}
     */
    elementAttrs(): Record<string, unknown>;
    /**
     * Get the post's content.
     *
     * @return {import('mithril').Children}
     */
    content(): import('mithril').Children;
    /**
     * Get the post's classes.
     *
     * @param {string} existing
     * @returns {string[]}
     */
    classes(existing: string): string[];
    /**
     * Build an item list for the post's actions.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    actionItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the post's footer.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    footerItems(): ItemList<import('mithril').Children>;
}
import Component from "../../common/Component";
import SubtreeRetainer from "../../common/utils/SubtreeRetainer";
import ItemList from "../../common/utils/ItemList";
