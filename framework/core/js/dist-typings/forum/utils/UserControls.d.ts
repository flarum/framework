declare namespace _default {
    /**
     * Get a list of controls for a user.
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    function controls(user: import("../../common/models/User").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get a list of controls for a user.
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    function controls(user: import("../../common/models/User").default, context: import("../../common/Component").default<any, any>): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user pertaining to the current user (e.g. poke, follow).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function userControls(): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user pertaining to the current user (e.g. poke, follow).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function userControls(): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user pertaining to moderation (e.g. suspend, edit).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function moderationControls(user: import("../../common/models/User").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user pertaining to moderation (e.g. suspend, edit).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function moderationControls(user: import("../../common/models/User").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user which are destructive (e.g. delete).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function destructiveControls(user: import("../../common/models/User").default): ItemList<import("mithril").Children>;
    /**
     * Get controls for a user which are destructive (e.g. delete).
     *
     * @param {import('../../common/models/User').default} user
     * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
     *
     * @return {ItemList<import('mithril').Children>}
     * @protected
     */
    function destructiveControls(user: import("../../common/models/User").default): ItemList<import("mithril").Children>;
    /**
     * Delete the user.
     *
     * @param {import('../../common/models/User').default} user
     */
    function deleteAction(user: import("../../common/models/User").default): void;
    /**
     * Delete the user.
     *
     * @param {import('../../common/models/User').default} user
     */
    function deleteAction(user: import("../../common/models/User").default): void;
    /**
     * Show deletion alert of user.
     *
     * @param {import('../../common/models/User').default} user
     * @param {string} type
     */
    function showDeletionAlert(user: import("../../common/models/User").default, type: string): void;
    /**
     * Show deletion alert of user.
     *
     * @param {import('../../common/models/User').default} user
     * @param {string} type
     */
    function showDeletionAlert(user: import("../../common/models/User").default, type: string): void;
    /**
     * Edit the user.
     *
     * @param {import('../../common/models/User').default} user
     */
    function editAction(user: import("../../common/models/User").default): void;
    /**
     * Edit the user.
     *
     * @param {import('../../common/models/User').default} user
     */
    function editAction(user: import("../../common/models/User").default): void;
}
export default _default;
import ItemList from "../../common/utils/ItemList";
