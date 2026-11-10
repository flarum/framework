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
    hero(): JSX.Element;
    sidebar(): JSX.Element;
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
     *
     * A user in the store is shown straight away, but the record may have arrived in
     * another endpoint's payload, carrying only the relationships that endpoint
     * included. Every user has `joinTime` whichever endpoint it came from, so that
     * says nothing about what else is loaded: whatever the user endpoint includes by
     * default, which is anything an extension adds to a profile, can be missing. So
     * such a user is fetched in the background, once, and the response updates the
     * same record the page is showing.
     *
     * Resolves once `this.user` is set so that subclasses can safely chain
     * dependent work (e.g. fetching related resources keyed off the user id). It
     * does not wait for that background fetch.
     */
    loadUser(username: string): Promise<void>;
    /**
     * Fetch a user from the user endpoint, unless that has already been done, to
     * fill in what the payload they first arrived in did not carry.
     *
     * It is silent: the page already has a user to show, so a failure is not worth an
     * alert, and the next visit tries again.
     */
    protected completeUser(user: User): void;
    /**
     * Build an item list for the content of the sidebar.
     */
    sidebarItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the navigation in the sidebar.
     */
    navItems(): ItemList<Mithril.Children>;
}
