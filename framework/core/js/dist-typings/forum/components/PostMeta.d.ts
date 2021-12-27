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
    /**
     * Get the permalink for the given post.
     *
     * @param {import('../../common/models/Post').default} post
     * @returns {string}
     */
    getPermalink(post: import('../../common/models/Post').default): string;
}
import Component from "../../common/Component";
