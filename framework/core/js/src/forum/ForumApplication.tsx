import app from './app';

import History from './utils/History';
import Pane from './utils/Pane';
import DiscussionPage from './components/DiscussionPage';
import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import DiscussionRenamedNotification from './components/DiscussionRenamedNotification';
import CommentPost from './components/CommentPost';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';
import routes, { ForumRoutes, makeRouteHelpers } from './routes';
import Application, { ApplicationData } from '../common/Application';
import Button from '../common/components/Button';
import Navigation from '../common/components/Navigation';
import NotificationListState from './states/NotificationListState';
import GlobalSearchState from './states/GlobalSearchState';
import DiscussionListState from './states/DiscussionListState';
import ComposerState from './states/ComposerState';
import isSafariMobile from './utils/isSafariMobile';

import type Notification from './components/Notification';
import type Post from './components/Post';
import type Discussion from '../common/models/Discussion';
import type NotificationModel from '../common/models/Notification';
import type PostModel from '../common/models/Post';
import extractText from '../common/utils/extractText';
import Notices from './components/Notices';
import Footer from './components/Footer';
import SearchManager from '../common/SearchManager';

export interface ForumApplicationData extends ApplicationData {}

export default class ForumApplication extends Application {
  /**
   * A map of notification types to their components.
   */
  notificationComponents: Record<string, ComponentClass<{ notification: NotificationModel }, Notification<{ notification: NotificationModel }>>> = {
    discussionRenamed: DiscussionRenamedNotification,
  };

  /**
   * A map of post types to their components.
   */
  postComponents: Record<string, ComponentClass<{ post: PostModel }, Post<{ post: PostModel }>>> = {
    comment: CommentPost,
    discussionRenamed: DiscussionRenamedPost,
  };

  /**
   * An object which controls the state of the page's side pane.
   */
  pane: Pane | null = null;

  /**
   * The app's history stack, which keeps track of which routes the user visits
   * so that they can easily navigate back to the previous route.
   */
  history: History = new History();

  /**
   * An object which controls the state of the user's notifications.
   */
  notifications: NotificationListState = new NotificationListState();

  /**
   * An object which stores the global search state and manages search capabilities.
   */
  search: SearchManager<GlobalSearchState> = new SearchManager(new GlobalSearchState());

  /**
   * An object which controls the state of the composer.
   */
  composer: ComposerState = new ComposerState();

  /**
   * An object which controls the state of the cached discussion list, which
   * is used in the index page and the slideout pane.
   */
  discussions: DiscussionListState = new DiscussionListState({});

  route: typeof Application.prototype.route & ForumRoutes;

  data!: ForumApplicationData;

  constructor() {
    super();

    routes(this);

    this.route = Object.assign((Object.getPrototypeOf(Object.getPrototypeOf(this)) as Application).route.bind(this), makeRouteHelpers(this));
  }

  /**
   * @inheritdoc
   */
  mount() {
    // Get the configured default route and update that route's path to be '/'.
    // Push the homepage as the first route, so that the user will always be
    // able to click on the 'back' button to go home, regardless of which page
    // they started on.
    const defaultRoute = this.forum.attribute('defaultRoute');
    let defaultAction = 'index';

    for (const i in this.routes) {
      if (this.routes[i].path === defaultRoute) defaultAction = i;
    }

    this.routes[defaultAction].path = '/';
    this.history.push(defaultAction, extractText(this.translator.trans('core.forum.header.back_to_index_tooltip')), '/');

    this.pane = new Pane(document.getElementById('app'));

    m.route.prefix = '';
    super.mount(this.forum.attribute('basePath'));

    // We mount navigation and header components after the page, so components
    // like the back button can access the updated state when rendering.
    m.mount(document.getElementById('app-navigation')!, { view: () => <Navigation className="App-backControl" drawer /> });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('notices')!, Notices);
    m.mount(document.getElementById('footer')!, Footer);

    // Route the home link back home when clicked. We do not want it to register
    // if the user is opening it in a new tab, however.
    document.getElementById('home-link')!.addEventListener('click', (e) => {
      if (e.ctrlKey || e.metaKey || e.button === 1) return;
      e.preventDefault();
      app.history.home();

      // Reload the current user so that their unread notification count is refreshed.
      const userId = app.session.user?.id();
      if (userId) {
        app.store.find('users', userId);
        m.redraw();
      }
    });

    if (isSafariMobile()) {
      $(() => {
        $('.App').addClass('mobile-safari');
      });
    }
  }

  /**
   * Check whether or not the user is currently viewing a discussion.
   */
  public viewingDiscussion(discussion: Discussion): boolean {
    return this.current.matches(DiscussionPage, { discussion });
  }

  /**
   * Whether we have already alerted the user that the forum's assets have been
   * updated since they loaded the page. Ensures the prompt is shown only once.
   */
  private assetsRevisionAlertShown = false;

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
  public checkAssetsRevision(serverRevision: string | null): void {
    const bootedRevision = this.data?.assetsRevision;

    if (!serverRevision || !bootedRevision || serverRevision === bootedRevision || this.assetsRevisionAlertShown) {
      return;
    }

    this.assetsRevisionAlertShown = true;

    this.alerts.show(
      {
        type: 'warning',
        dismissible: true,
        controls: [
          <Button className="Button Button--link" onclick={() => window.location.reload()}>
            {app.translator.trans('core.lib.assets_updated.reload_button')}
          </Button>,
        ],
      },
      app.translator.trans('core.lib.assets_updated.message')
    );
  }
}
