/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class CommentPost extends Post {
    /**
     * If the post has been hidden, then this flag determines whether or not its
     * content has been expanded.
     *
     * @type {Boolean}
     */
    revealContent: boolean | undefined;
    /**
     * Whether or not the user hover card inside of PostUser is visible.
     * The property must be managed in CommentPost to be able to use it in the subtree check
     *
     * @type {Boolean}
     */
    cardVisible: boolean | undefined;
    refreshContent(): void;
    contentHtml: any;
    isEditing(): any;
    /**
     * Toggle the visibility of a hidden post's content.
     */
    toggleContent(): void;
    /**
     * Build an item list for the post's header.
     *
     * @return {ItemList}
     */
    headerItems(): ItemList;
}
import Post from "./Post";
import ItemList from "../../common/utils/ItemList";
