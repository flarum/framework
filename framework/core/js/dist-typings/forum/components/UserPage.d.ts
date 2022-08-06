import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';
import type Mithril from 'mithril';
export interface IUserPageAttrs extends IPageAttrs {
}
/**
 * The `UserPage` component shows a user's profile. It can be extended to show
 * content inside of the content area. See `ActivityPage` and `SettingsPage` for
 * examples.
 *
 * @abstract
 */
export default class UserPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs, CustomState = undefined> extends Page<CustomAttrs, CustomState> {
    /**
     * The user this page is for.
     */
    user: User | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    /**
     * Base view template for the user page.
     */
    view(): JSX.Element;
    /**
     * Get the content to display in the user page.
     */
    content(): Mithril.Children | void;
    /**
     * Initialize the component with a user, and trigger the loading of their
     * activity feed.
     *
     * @protected
     */
    show(user: User): void;
    /**
     * Given a username, load the user's profile from the store, or make a request
     * if we don't have it yet. Then initialize the profile page with that user.
     */
    loadUser(username: string): void;
    /**
     * Build an item list for the content of the sidebar.
     */
    sidebarItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the navigation in the sidebar.
     */
    navItems(): ItemList<Mithril.Children>;
}
