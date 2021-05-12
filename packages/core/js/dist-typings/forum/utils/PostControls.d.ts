declare namespace _default {
    /**
     * Get a list of controls for a post.
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @public
     */
    function controls(post: any, context: any): ItemList;
    /**
     * Get a list of controls for a post.
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @public
     */
    function controls(post: any, context: any): ItemList;
    /**
     * Get controls for a post pertaining to the current user (e.g. report).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function userControls(post: any, context: any): ItemList;
    /**
     * Get controls for a post pertaining to the current user (e.g. report).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function userControls(post: any, context: any): ItemList;
    /**
     * Get controls for a post pertaining to moderation (e.g. edit).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function moderationControls(post: any, context: any): ItemList;
    /**
     * Get controls for a post pertaining to moderation (e.g. edit).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function moderationControls(post: any, context: any): ItemList;
    /**
     * Get controls for a post that are destructive (e.g. delete).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function destructiveControls(post: any, context: any): ItemList;
    /**
     * Get controls for a post that are destructive (e.g. delete).
     *
     * @param {Post} post
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function destructiveControls(post: any, context: any): ItemList;
    /**
     * Open the composer to edit a post.
     *
     * @return {Promise}
     */
    function editAction(): Promise<any>;
    /**
     * Open the composer to edit a post.
     *
     * @return {Promise}
     */
    function editAction(): Promise<any>;
    /**
     * Hide a post.
     *
     * @return {Promise}
     */
    function hideAction(): Promise<any>;
    /**
     * Hide a post.
     *
     * @return {Promise}
     */
    function hideAction(): Promise<any>;
    /**
     * Restore a post.
     *
     * @return {Promise}
     */
    function restoreAction(): Promise<any>;
    /**
     * Restore a post.
     *
     * @return {Promise}
     */
    function restoreAction(): Promise<any>;
    /**
     * Delete a post.
     *
     * @return {Promise}
     */
    function deleteAction(context: any): Promise<any>;
    /**
     * Delete a post.
     *
     * @return {Promise}
     */
    function deleteAction(context: any): Promise<any>;
}
export default _default;
import ItemList from "../../common/utils/ItemList";
