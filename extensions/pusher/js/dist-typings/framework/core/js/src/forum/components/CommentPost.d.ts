/**
 * The `CommentPost` component displays a standard `comment`-typed post. This
 * includes a number of item lists (controls, header, and footer) surrounding
 * the post's HTML content.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class CommentPost extends Post<import("./Post").IPostAttrs> {
    constructor();
    oninit(vnode: any): void;
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
    content(): any;
    refreshContent(): void;
    contentHtml: any;
    oncreate(vnode: any): void;
    onupdate(vnode: any): void;
    isEditing(): boolean;
    /**
     * Toggle the visibility of a hidden post's content.
     */
    toggleContent(): void;
    /**
     * Build an item list for the post's header.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    headerItems(): ItemList<import('mithril').Children>;
}
import Post from "./Post";
import ItemList from "../../common/utils/ItemList";
