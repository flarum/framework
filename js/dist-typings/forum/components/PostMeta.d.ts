/**
 * The `PostMeta` component displays the time of a post, and when clicked, shows
 * a dropdown containing more information about the post (number, full time,
 * permalink).
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostMeta extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Get the permalink for the given post.
     *
     * @param {Post} post
     * @returns {String}
     */
    getPermalink(post: any): string;
}
import Component from "../../common/Component";
