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
import SearchManager from '../common/SearchManager';
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
     * An object which stores the global search state and manages search capabilities.
     */
    search: SearchManager<GlobalSearchState>;
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
     * Whether we have already alerted the user that the forum's assets have been
     * updated since they loaded the page. Ensures the prompt is shown only once.
     */
    private assetsRevisionAlertShown;
    /**
     * When the server reports an asset revision (on an API response) that differs
     * from the one this page booted with, the forum's JS/CSS has been rebuilt since
     * load. Prompt the user to reload to pick up the new assets, at most once.
     *
     * Both values are produced server-side (see `AssetsRevision`), so they are
     * directly comparable regardless of which versioner the forum uses.
     *
     * Public so the realtime extension can call it with a pushed revision token.
     */
    checkAssetsRevision(serverRevision: string | null): void;
}
