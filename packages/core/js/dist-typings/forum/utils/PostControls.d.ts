declare namespace _default {
    /**
     * Get a list of controls for a post.
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     */
    function controls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get a list of controls for a post.
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     */
    function controls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post pertaining to the current user (e.g. report).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function userControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post pertaining to the current user (e.g. report).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function userControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post pertaining to moderation (e.g. edit).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function moderationControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post pertaining to moderation (e.g. edit).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function moderationControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post that are destructive (e.g. delete).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function destructiveControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a post that are destructive (e.g. delete).
     *
     * @param {import('../../common/models/Post').default} post
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}')}
     * @protected
     */
    function destructiveControls(post: import("../../common/models/Post").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Open the composer to edit a post.
     *
     * @return {Promise<void>}
     */
    function editAction(): Promise<void>;
    /**
     * Open the composer to edit a post.
     *
     * @return {Promise<void>}
     */
    function editAction(): Promise<void>;
    /**
     * Hide a post.
     *
     * @return {Promise<void>}
     */
    function hideAction(): Promise<void>;
    /**
     * Hide a post.
     *
     * @return {Promise<void>}
     */
    function hideAction(): Promise<void>;
    /**
     * Restore a post.
     *
     * @return {Promise<void>}
     */
    function restoreAction(): Promise<void>;
    /**
     * Restore a post.
     *
     * @return {Promise<void>}
     */
    function restoreAction(): Promise<void>;
    /**
     * Delete a post.
     *
     * @return {Promise<void>}
     */
    function deleteAction(context: any): Promise<void>;
    /**
     * Delete a post.
     *
     * @return {Promise<void>}
     */
    function deleteAction(context: any): Promise<void>;
}
export default _default;
import ItemList from "../../common/utils/ItemList";
