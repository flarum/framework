/**
 * The `UserPage` component shows a user's profile. It can be extended to show
 * content inside of the content area. See `ActivityPage` and `SettingsPage` for
 * examples.
 *
 * @abstract
 */
export default class UserPage extends Page {
    /**
     * The user this page is for.
     *
     * @type {User}
     */
    user: any;
    /**
     * Get the content to display in the user page.
     *
     * @return {VirtualElement}
     */
    content(): any;
    /**
     * Initialize the component with a user, and trigger the loading of their
     * activity feed.
     *
     * @param {User} user
     * @protected
     */
    protected show(user: any): void;
    /**
     * Given a username, load the user's profile from the store, or make a request
     * if we don't have it yet. Then initialize the profile page with that user.
     *
     * @param {String} username
     */
    loadUser(username: string): void;
    /**
     * Build an item list for the content of the sidebar.
     *
     * @return {ItemList}
     */
    sidebarItems(): ItemList;
    /**
     * Build an item list for the navigation in the sidebar.
     *
     * @return {ItemList}
     */
    navItems(): ItemList;
}
import Page from "../../common/components/Page";
import ItemList from "../../common/utils/ItemList";
