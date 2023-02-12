declare namespace _default {
    /**
     * Get a list of controls for a discussion.
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>} context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    function controls(discussion: import("../../common/models/Discussion").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get a list of controls for a discussion.
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>} context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    function controls(discussion: import("../../common/models/Discussion").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion pertaining to the current user (e.g. reply,
     * follow).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function userControls(discussion: import("../../common/models/Discussion").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion pertaining to the current user (e.g. reply,
     * follow).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function userControls(discussion: import("../../common/models/Discussion").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function moderationControls(discussion: import("../../common/models/Discussion").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function moderationControls(discussion: import("../../common/models/Discussion").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion which are destructive (e.g. delete).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function destructiveControls(discussion: import("../../common/models/Discussion").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a discussion which are destructive (e.g. delete).
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function destructiveControls(discussion: import("../../common/models/Discussion").default): ItemList<import("mithril").Children>;
    /**
     * Open the reply composer for the discussion. A promise will be returned,
     * which resolves when the composer opens successfully. If the user is not
     * logged in, they will be prompted. If they don't have permission to
     * reply, the promise will be rejected.
     *
     * @param {boolean} goToLast Whether or not to scroll down to the last post if the discussion is being viewed.
     * @param {boolean} forceRefresh Whether or not to force a reload of the composer component, even if it is already open for this discussion.
     *
     * @return {Promise<void>}
     */
    function replyAction(goToLast: boolean, forceRefresh: boolean): Promise<void>;
    /**
     * Open the reply composer for the discussion. A promise will be returned,
     * which resolves when the composer opens successfully. If the user is not
     * logged in, they will be prompted. If they don't have permission to
     * reply, the promise will be rejected.
     *
     * @param {boolean} goToLast Whether or not to scroll down to the last post if the discussion is being viewed.
     * @param {boolean} forceRefresh Whether or not to force a reload of the composer component, even if it is already open for this discussion.
     *
     * @return {Promise<void>}
     */
    function replyAction(goToLast: boolean, forceRefresh: boolean): Promise<void>;
    /**
     * Hide a discussion.
     *
     * @return {Promise<void>}
     */
    function hideAction(): Promise<void>;
    /**
     * Hide a discussion.
     *
     * @return {Promise<void>}
     */
    function hideAction(): Promise<void>;
    /**
     * Restore a discussion.
     *
     * @return {Promise<void>}
     */
    function restoreAction(): Promise<void>;
    /**
     * Restore a discussion.
     *
     * @return {Promise<void>}
     */
    function restoreAction(): Promise<void>;
    /**
     * Delete the discussion after confirming with the user.
     *
     * @return {Promise<void>}
     */
    function deleteAction(): Promise<void>;
    /**
     * Delete the discussion after confirming with the user.
     *
     * @return {Promise<void>}
     */
    function deleteAction(): Promise<void>;
    /**
     * Rename the discussion.
     */
    function renameAction(): any;
    /**
     * Rename the discussion.
     */
    function renameAction(): any;
}
export default _default;
import ItemList from "../../common/utils/ItemList";
