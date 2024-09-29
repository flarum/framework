/**
 * The `PostMeta` component displays the time of a post, and when clicked, shows
 * a dropdown containing more information about the post (number, full time,
 * permalink).
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostMeta extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * Get the permalink for the given post.
     *
     * @param {import('../../common/models/Post').default} post
     * @returns {string}
     */
    getPermalink(post: import('../../common/models/Post').default): string;
    /**
     * When the dropdown menu is shown, select the contents of the permalink
     * input so that the user can quickly copy the URL.
     * @param {Event} e
     */
    selectPermalink(e: Event): void;
    /**
     * @returns {ItemList}
     */
    viewItems(): ItemList<any>;
    /**
     * @returns {ItemList}
     */
    metaItems(): ItemList<any>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
