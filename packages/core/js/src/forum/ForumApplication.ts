import app from '../forum/app';

import History from './utils/History';
import Pane from './utils/Pane';
import DiscussionPage from './components/DiscussionPage';
import SignUpModal from './components/SignUpModal';
import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import Composer from './components/Composer';
import DiscussionRenamedNotification from './components/DiscussionRenamedNotification';
import CommentPost from './components/CommentPost';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';
import routes, { makeRouteHelpers } from './routes';
import alertEmailConfirmation from './utils/alertEmailConfirmation';
import Application from '../common/Application';
import Navigation from '../common/components/Navigation';
import NotificationListState from './states/NotificationListState';
import GlobalSearchState from './states/GlobalSearchState';
import DiscussionListState from './states/DiscussionListState';
import ComposerState from './states/ComposerState';
import isSafariMobile from './utils/isSafariMobile';

import type Notification from './components/Notification';
import type Post from './components/Post';
import Discussion from '../common/models/Discussion';
import extractText from '../common/utils/extractText';

export default class ForumApplication extends Application {
  /**
   * A map of notification types to their components.
   */
  notificationComponents: Record<string, typeof Notification> = {
    discussionRenamed: DiscussionRenamedNotification,
  };

  /**
   * A map of post types to their components.
   */
  postComponents: Record<string, typeof Post> = {
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
   * An object which stores previously searched queries and provides convenient
   * tools for retrieving and managing search values.
   */
  search: GlobalSearchState = new GlobalSearchState();

  /**
   * An object which controls the state of the composer.
   */
  composer: ComposerState = new ComposerState();

  /**
   * An object which controls the state of the cached discussion list, which
   * is used in the index page and the slideout pane.
   */
  discussions: DiscussionListState = new DiscussionListState({});

  route: typeof Application.prototype.route & ReturnType<typeof makeRouteHelpers>;

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
    m.mount(document.getElementById('app-navigation')!, { view: () => Navigation.component({ className: 'App-backControl', drawer: true }) });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('composer')!, { view: () => Composer.component({ state: this.composer }) });

    alertEmailConfirmation(this);

    // Route the home link back home when clicked. We do not want it to register
    // if the user is opening it in a new tab, however.
    document.getElementById('home-link')!.addEventListener('click', (e) => {
      if (e.ctrlKey || e.metaKey || e.which === 2) return;
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
   * Callback for when an external authenticator (social login) action has
   * completed.
   *
   * If the payload indicates that the user has been logged in, then the page
   * will be reloaded. Otherwise, a SignUpModal will be opened, prefilled
   * with the provided details.
   */
  public authenticationComplete(payload: Record<string, unknown>): void {
    if (payload.loggedIn) {
      window.location.reload();
    } else {
      this.modal.show(SignUpModal, payload);
    }
  }
}
