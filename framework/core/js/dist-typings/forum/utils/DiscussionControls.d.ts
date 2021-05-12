declare namespace _default {
    /**
     * Get a list of controls for a discussion.
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @public
     */
    function controls(discussion: any, context: any): ItemList;
    /**
     * Get a list of controls for a discussion.
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @public
     */
    function controls(discussion: any, context: any): ItemList;
    /**
     * Get controls for a discussion pertaining to the current user (e.g. reply,
     * follow).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function userControls(discussion: any, context: any): ItemList;
    /**
     * Get controls for a discussion pertaining to the current user (e.g. reply,
     * follow).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function userControls(discussion: any, context: any): ItemList;
    /**
     * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function moderationControls(discussion: any): ItemList;
    /**
     * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function moderationControls(discussion: any): ItemList;
    /**
     * Get controls for a discussion which are destructive (e.g. delete).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function destructiveControls(discussion: any): ItemList;
    /**
     * Get controls for a discussion which are destructive (e.g. delete).
     *
     * @param {Discussion} discussion
     * @param {*} context The parent component under which the controls menu will
     *     be displayed.
     * @return {ItemList}
     * @protected
     */
    function destructiveControls(discussion: any): ItemList;
    /**
     * Open the reply composer for the discussion. A promise will be returned,
     * which resolves when the composer opens successfully. If the user is not
     * logged in, they will be prompted. If they don't have permission to
     * reply, the promise will be rejected.
     *
     * @param {Boolean} goToLast Whether or not to scroll down to the last post if
     *     the discussion is being viewed.
     * @param {Boolean} forceRefresh Whether or not to force a reload of the
     *     composer component, even if it is already open for this discussion.
     * @return {Promise}
     */
    function replyAction(goToLast: boolean, forceRefresh: boolean): Promise<any>;
    /**
     * Open the reply composer for the discussion. A promise will be returned,
     * which resolves when the composer opens successfully. If the user is not
     * logged in, they will be prompted. If they don't have permission to
     * reply, the promise will be rejected.
     *
     * @param {Boolean} goToLast Whether or not to scroll down to the last post if
     *     the discussion is being viewed.
     * @param {Boolean} forceRefresh Whether or not to force a reload of the
     *     composer component, even if it is already open for this discussion.
     * @return {Promise}
     */
    function replyAction(goToLast: boolean, forceRefresh: boolean): Promise<any>;
    /**
     * Hide a discussion.
     *
     * @return {Promise}
     */
    function hideAction(): Promise<any>;
    /**
     * Hide a discussion.
     *
     * @return {Promise}
     */
    function hideAction(): Promise<any>;
    /**
     * Restore a discussion.
     *
     * @return {Promise}
     */
    function restoreAction(): Promise<any>;
    /**
     * Restore a discussion.
     *
     * @return {Promise}
     */
    function restoreAction(): Promise<any>;
    /**
     * Delete the discussion after confirming with the user.
     *
     * @return {Promise}
     */
    function deleteAction(): Promise<any>;
    /**
     * Delete the discussion after confirming with the user.
     *
     * @return {Promise}
     */
    function deleteAction(): Promise<any>;
    /**
     * Rename the discussion.
     *
     * @return {Promise}
     */
    function renameAction(): Promise<any>;
    /**
     * Rename the discussion.
     *
     * @return {Promise}
     */
    function renameAction(): Promise<any>;
}
export default _default;
import ItemList from "../../common/utils/ItemList";
