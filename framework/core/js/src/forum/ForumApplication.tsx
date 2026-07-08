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
   * Whether newer assets have been detected since this page booted. Once set, the
   * user's next navigation becomes a full page load so the fresh assets are picked
   * up — see {@link checkAssetsRevision} and {@link refreshOnNextNavigation}.
   */
  private assetsRefreshPending = false;

  /**
   * When the server reports an asset revision (on an API response, or one pushed
   * by the realtime extension) that differs from the one this page booted with,
   * the forum's JS/CSS has been rebuilt since load.
   *
   * Rather than interrupt with a reload prompt — which pops up mid-read or
   * mid-compose, and fires for every visitor the moment an admin toggles an
   * extension — we just remember it. The user's next real navigation then loads
   * the fresh assets naturally, without ever interrupting a reading or typing
   * session, and with no risk of discarding an open draft. (Approach proposed by
   * @luceos.)
   *
   * Both values are produced server-side (see `AssetsRevision`), so they are
   * directly comparable regardless of which versioner the forum uses.
   *
   * Public so the realtime extension can call it with a pushed revision token.
   */
  public checkAssetsRevision(serverRevision: string | null): void {
    const bootedRevision = this.data?.assetsRevision;

    if (!serverRevision || !bootedRevision || serverRevision === bootedRevision || this.assetsRefreshPending) {
      return;
    }

    this.assetsRefreshPending = true;
    this.refreshOnNextNavigation();
  }

  /**
   * Turn the user's next navigation into a full page load, so it boots with the
   * freshly-built assets. Runs in the capture phase (and stops propagation) so it
   * takes over before the router's own `<Link>` click handling, but only for a
   * plain left-click on an internal, same-origin link: modified clicks (open in
   * new tab/window), `target="_blank"` / `download` links, in-page anchors and
   * non-navigation schemes are all left untouched. Back/forward reloads too.
   *
   * Set up only once, lazily, the first time newer assets are detected.
   */
  private refreshOnNextNavigation(): void {
    document.addEventListener(
      'click',
      (e) => {
        if (!this.assetsRefreshPending || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
          return;
        }

        const anchor = (e.target as HTMLElement | null)?.closest?.('a[href]') as HTMLAnchorElement | null;
        if (!anchor || anchor.target === '_blank' || anchor.hasAttribute('download')) {
          return;
        }

        const href = anchor.getAttribute('href') || '';
        if (!href || href.startsWith('#') || /^(javascript|mailto|tel):/i.test(href)) {
          return;
        }

        let url: URL;
        try {
          url = new URL(anchor.href, window.location.href);
        } catch {
          return;
        }

        // Same-origin only, and not a bare hash change on the current page.
        if (url.origin !== window.location.origin) {
          return;
        }
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
          return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.assign(url.href);
      },
      true
    );

    window.addEventListener('popstate', () => {
      if (this.assetsRefreshPending) {
        window.location.reload();
      }
    });
  }
}
