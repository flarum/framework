import History from './utils/History';
import Pane from './utils/Pane';
import { ForumRoutes } from './routes';
import Application, { ApplicationData } from '../common/Application';
import NotificationListState from './states/NotificationListState';
import GlobalSearchState from './states/GlobalSearchState';
import DiscussionListState from './states/DiscussionListState';
import ComposerState from './states/ComposerState';
import type Notification from './components/Notification';
import type Post from './components/Post';
import type Discussion from '../common/models/Discussion';
import type NotificationModel from '../common/models/Notification';
import type PostModel from '../common/models/Post';
export interface ForumApplicationData extends ApplicationData {
}
export default class ForumApplication extends Application {
    /**
     * A map of notification types to their components.
     */
    notificationComponents: Record<string, ComponentClass<{
        notification: NotificationModel;
    }, Notification<{
        notification: NotificationModel;
    }>>>;
    /**
     * A map of post types to their components.
     */
    postComponents: Record<string, ComponentClass<{
        post: PostModel;
    }, Post<{
        post: PostModel;
    }>>>;
    /**
     * An object which controls the state of the page's side pane.
     */
    pane: Pane | null;
    /**
     * The app's history stack, which keeps track of which routes the user visits
     * so that they can easily navigate back to the previous route.
     */
    history: History;
    /**
     * An object which controls the state of the user's notifications.
     */
    notifications: NotificationListState;
    /**
     * An object which stores previously searched queries and provides convenient
     * tools for retrieving and managing search values.
     */
    search: GlobalSearchState;
    /**
     * An object which controls the state of the composer.
     */
    composer: ComposerState;
    /**
     * An object which controls the state of the cached discussion list, which
     * is used in the index page and the slideout pane.
     */
    discussions: DiscussionListState;
    route: typeof Application.prototype.route & ForumRoutes;
    data: ForumApplicationData;
    constructor();
    /**
     * @inheritdoc
     */
    mount(): void;
    /**
     * Check whether or not the user is currently viewing a discussion.
     */
    viewingDiscussion(discussion: Discussion): boolean;
    /**
     * Callback for when an external authenticator (social login) action has
     * completed.
     *
     * If the payload indicates that the user has been logged in, then the page
     * will be reloaded. Otherwise, a SignUpModal will be opened, prefilled
     * with the provided details.
     */
    authenticationComplete(payload: Record<string, unknown>): void;
}
