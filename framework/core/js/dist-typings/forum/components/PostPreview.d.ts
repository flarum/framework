/**
 * The `PostPreview` component shows a link to a post containing the avatar and
 * username of the author, and a short excerpt of the post's content.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostPreview extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * @returns {string|undefined|null}
     */
    content(): string | undefined | null;
    /**
     * @returns {string}
     */
    excerpt(): string;
}
import Component from "../../common/Component";
