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
export default class Post extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
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
     * @return {Object}
     */
    elementAttrs(): Object;
    /**
     * Get the post's content.
     *
     * @return {Array}
     */
    content(): any[];
    /**
     * Get the post's classes.
     *
     * @param string classes
     * @returns {string[]}
     */
    classes(existing: any): string[];
    /**
     * Build an item list for the post's actions.
     *
     * @return {ItemList}
     */
    actionItems(): ItemList;
    /**
     * Build an item list for the post's footer.
     *
     * @return {ItemList}
     */
    footerItems(): ItemList;
}
import Component from "../../common/Component";
import SubtreeRetainer from "../../common/utils/SubtreeRetainer";
import ItemList from "../../common/utils/ItemList";
