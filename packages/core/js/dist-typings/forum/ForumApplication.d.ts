export default class ForumApplication extends Application {
    /**
     * A map of notification types to their components.
     *
     * @type {Object}
     */
    notificationComponents: Object;
    /**
     * A map of post types to their components.
     *
     * @type {Object}
     */
    postComponents: Object;
    /**
     * An object which controls the state of the page's side pane.
     *
     * @type {Pane}
     */
    pane: Pane;
    /**
     * The app's history stack, which keeps track of which routes the user visits
     * so that they can easily navigate back to the previous route.
     *
     * @type {History}
     */
    history: History;
    /**
     * An object which controls the state of the user's notifications.
     *
     * @type {NotificationListState}
     */
    notifications: NotificationListState;
    search: GlobalSearchState;
    composer: ComposerState;
    /**
     * An object which controls the state of the cached discussion list, which
     * is used in the index page and the slideout pane.
     *
     * @type {DiscussionListState}
     */
    discussions: DiscussionListState;
    /**
     * Check whether or not the user is currently viewing a discussion.
     *
     * @param {Discussion} discussion
     * @return {Boolean}
     */
    viewingDiscussion(discussion: any): boolean;
    /**
     * Callback for when an external authenticator (social login) action has
     * completed.
     *
     * If the payload indicates that the user has been logged in, then the page
     * will be reloaded. Otherwise, a SignUpModal will be opened, prefilled
     * with the provided details.
     *
     * @param {Object} payload A dictionary of attrs to pass into the sign up
     *     modal. A truthy `loggedIn` attr indicates that the user has logged
     *     in, and thus the page is reloaded.
     * @public
     */
    public authenticationComplete(payload: Object): void;
}
import Application from "../common/Application";
import Pane from "./utils/Pane";
import History from "./utils/History";
import NotificationListState from "./states/NotificationListState";
import GlobalSearchState from "./states/GlobalSearchState";
import ComposerState from "./states/ComposerState";
import DiscussionListState from "./states/DiscussionListState";
