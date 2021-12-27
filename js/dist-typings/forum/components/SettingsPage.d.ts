/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage extends UserPage {
    /**
     * Build an item list for the user's settings controls.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    settingsItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the user's account settings.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    accountItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the user's notification settings.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    notificationsItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the user's privacy settings.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    privacyItems(): ItemList<import('mithril').Children>;
    discloseOnlineLoading: boolean | undefined;
}
import UserPage from "./UserPage";
import ItemList from "../../common/utils/ItemList";
