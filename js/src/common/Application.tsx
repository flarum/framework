import app from '../common/app';

import ItemList from './utils/ItemList';
import Button from './components/Button';
import ModalManager from './components/ModalManager';
import AlertManager from './components/AlertManager';
import RequestErrorModal from './components/RequestErrorModal';
import Translator from './Translator';
import Store from './Store';
import Session from './Session';
import extract from './utils/extract';
import Drawer from './utils/Drawer';
import mapRoutes from './utils/mapRoutes';
import RequestError from './utils/RequestError';
import ScrollListener from './utils/ScrollListener';
import liveHumanTimes from './utils/liveHumanTimes';
import { extend } from './extend';

import Forum from './models/Forum';
import User from './models/User';
import Discussion from './models/Discussion';
import Post from './models/Post';
import Group from './models/Group';
import Notification from './models/Notification';
import PageState from './states/PageState';
import ModalManagerState from './states/ModalManagerState';
import AlertManagerState from './states/AlertManagerState';

import type DefaultResolver from './resolvers/DefaultResolver';
import type Mithril from 'mithril';
import type Component from './Component';
import type { ComponentAttrs } from './Component';

export type FlarumScreens = 'phone' | 'tablet' | 'desktop' | 'desktop-hd';

export type FlarumGenericRoute = RouteItem<
  Record<string, unknown>,
  Component<{ routeName: string; [key: string]: unknown }>,
  Record<string, unknown>
>;

export interface FlarumRequestOptions<ResponseType> extends Omit<Mithril.RequestOptions<ResponseType>, 'extract'> {
  errorHandler: (errorMessage: string) => void;
  url: string;
  // TODO: [Flarum 2.0] Remove deprecated option
  /**
   * Manipulate the response text before it is parsed into JSON.
   *
   * @deprecated Please use `modifyText` instead.
   */
  extract: (responseText: string) => string;
  /**
   * Manipulate the response text before it is parsed into JSON.
   *
   * This overrides any `extract` method provided.
   */
  modifyText: (responseText: string) => string;
}

/**
 * A valid route definition.
 */
export type RouteItem<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> = {
  /**
   * The path for your route.
   *
   * This might be a specific URL path (e.g.,`/myPage`), or it might
   * contain a variable used by a resolver (e.g., `/myPage/:id`).
   *
   * @see https://docs.flarum.org/extend/frontend-pages.html#route-resolvers-advanced
   */
  path: `/${string}`;
} & (
  | {
      /**
       * The component to render when this route matches.
       */
      component: { new (): Comp };
      /**
       * A custom resolver class.
       *
       * This should be the class itself, and **not** an instance of the
       * class.
       */
      resolverClass?: { new (): DefaultResolver<Attrs, Comp, RouteArgs> };
    }
  | {
      /**
       * An instance of a route resolver.
       */
      resolver: RouteResolver<Attrs, Comp, RouteArgs>;
    }
);

export interface RouteResolver<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> {
  /**
   * A method which selects which component to render based on
   * conditional logic.
   *
   * Returns the component class, and **not** a Vnode or JSX
   * expression.
   */
  onmatch(this: this, args: RouteArgs, requestedPath: string, route: string): { new (): Comp };
  /**
   * A function which renders the provided component.
   *
   * Returns a Mithril Vnode or other children.
   */
  render(this: this, vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children;
}

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
  /**
   * The forum model for this application.
   */
  forum!: Forum;

  /**
   * A map of routes, keyed by a unique route name. Each route is an object
   * containing the following properties:
   *
   * - `path` The path that the route is accessed at.
   * - `component` The Mithril component to render when this route is active.
   *
   * @example
   * app.routes.discussion = { path: '/d/:id', component: DiscussionPage };
   */
  routes: Record<string, FlarumGenericRoute> = {};

  /**
   * An ordered list of initializers to bootstrap the application.
   */
  initializers: ItemList<(app: this) => void> = new ItemList();

  /**
   * The app's session.
   *
   * Stores info about the current user.
   */
  session!: Session;

  /**
   * The app's translator.
   */
  translator: Translator = new Translator();

  /**
   * The app's data store.
   */
  store: Store = new Store({
    forums: Forum,
    users: User,
    discussions: Discussion,
    posts: Post,
    groups: Group,
    notifications: Notification,
  });

  /**
   * A local cache that can be used to store data at the application level, so
   * that is persists between different routes.
   */
  cache: Record<string, unknown> = {};

  /**
   * Whether or not the app has been booted.
   */
  booted: boolean = false;

  /**
   * The page the app is currently on.
   *
   * This object holds information about the type of page we are currently
   * visiting, and sometimes additional arbitrary page state that may be
   * relevant to lower-level components.
   */
  current: PageState = new PageState(null);

  /**
   * The page the app was on before the current page.
   *
   * Once the application navigates to another page, the object previously
   * assigned to this.current will be moved to this.previous, while this.current
   * is re-initialized.
   */
  previous: PageState = new PageState(null);

  /**
   * An object that manages modal state.
   */
  modal: ModalManagerState = new ModalManagerState();

  /**
   * An object that manages the state of active alerts.
   */
  alerts: AlertManagerState = new AlertManagerState();

  /**
   * An object that manages the state of the navigation drawer.
   */
  drawer!: Drawer;

  data!: {
    apiDocument: Record<string, unknown> | null;
    locale: string;
    locales: Record<string, string>;
    resources: Record<string, unknown>[];
    session: { userId: number; csrfToken: string };
    [key: string]: unknown;
  };

  private _title: string = '';
  private _titleCount: number = 0;

  private set title(val: string) {
    this._title = val;
  }

  get title() {
    return this._title;
  }

  private set titleCount(val: number) {
    this._titleCount = val;
  }

  get titleCount() {
    return this._titleCount;
  }

  /**
   * The key for an Alert that was shown as a result of an AJAX request error.
   * If present, it will be dismissed on the next successful request.
   */
  private requestErrorAlert: number | null = null;

  initialRoute!: string;

  load(payload: Application['data']) {
    this.data = payload;
    this.translator.setLocale(payload.locale);
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));

    this.store.pushPayload({ data: this.data.resources });

    this.forum = this.store.getById('forums', 1);

    this.session = new Session(this.store.getById('users', this.data.session.userId), this.data.session.csrfToken);

    this.mount();

    this.initialRoute = window.location.href;
  }

  // TODO: This entire system needs a do-over for v2
  bootExtensions(extensions: Record<string, { extend?: unknown[] }>) {
    Object.keys(extensions).forEach((name) => {
      const extension = extensions[name];

      // If an extension doesn't define extenders, there's nothing more to do here.
      if (!extension.extend) return;

      const extenders = extension.extend.flat(Infinity);

      for (const extender of extenders) {
        extender.extend(this, { name, exports: extension });
      }
    });
  }

  mount(basePath: string = '') {
    // An object with a callable view property is used in order to pass arguments to the component; see https://mithril.js.org/mount.html
    m.mount(document.getElementById('modal')!, { view: () => ModalManager.component({ state: this.modal }) });
    m.mount(document.getElementById('alerts')!, { view: () => AlertManager.component({ state: this.alerts }) });

    this.drawer = new Drawer();

    m.route(document.getElementById('content')!, basePath + '/', mapRoutes(this.routes, basePath));

    const appEl = document.getElementById('app')!;
    const appHeaderEl = document.querySelector('.App-header')!;

    // Add a class to the body which indicates that the page has been scrolled
    // down. When this happens, we'll add classes to the header and app body
    // which will set the navbar's position to fixed. We don't want to always
    // have it fixed, as that could overlap with custom headers.
    const scrollListener = new ScrollListener((top: number) => {
      const offset = appEl.getBoundingClientRect().top + document.body.scrollTop;

      appEl.classList.toggle('affix', top >= offset);
      appEl.classList.toggle('scrolled', top > offset);

      appHeaderEl.classList.toggle('navbar-fixed-top', top >= offset);
    });

    scrollListener.start();
    scrollListener.update();

    document.body.classList.add('ontouchstart' in window ? 'touch' : 'no-touch');

    liveHumanTimes();
  }

  /**
   * Get the API response document that has been preloaded into the application.
   */
  preloadedApiDocument(): Record<string, unknown> | null {
    // If the URL has changed, the preloaded Api document is invalid.
    if (this.data.apiDocument && window.location.href === this.initialRoute) {
      const results = this.store.pushPayload(this.data.apiDocument);

      this.data.apiDocument = null;

      return results;
    }

    return null;
  }

  /**
   * Determine the current screen mode, based on our media queries.
   */
  screen(): FlarumScreens {
    const styles = getComputedStyle(document.documentElement);
    return styles.getPropertyValue('--flarum-screen') as ReturnType<Application['screen']>;
  }

  /**
   * Set the `<title>` of the page.
   *
   * @param title New page title
   */
  setTitle(title: string): void {
    this.title = title;
    this.updateTitle();
  }

  /**
   * Set a number to display in the `<title>` of the page.
   *
   * @param count Number to display in title
   */
  setTitleCount(count: number): void {
    this.titleCount = count;
    this.updateTitle();
  }

  updateTitle(): void {
    const count = this.titleCount ? `(${this.titleCount}) ` : '';
    const pageTitleWithSeparator = this.title && m.route.get() !== this.forum.attribute('basePath') + '/' ? this.title + ' - ' : '';
    const title = this.forum.attribute('title');
    document.title = count + pageTitleWithSeparator + title;
  }

  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://mithril.js.org/request.html
   *
   * @param options
   * @return {Promise}
   */
  request<ResponseType>(originalOptions: FlarumRequestOptions<ResponseType>): Promise<ResponseType | string> {
    const options = { ...originalOptions };

    // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.
    options.background ||= true;

    extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
      xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken!);
    });

    // If the method is something like PATCH or DELETE, which not all servers
    // and clients support, then we'll send it as a POST request with the
    // intended method specified in the X-HTTP-Method-Override header.
    if (options.method && !['GET', 'POST'].includes(options.method)) {
      const method = options.method;

      extend(options, 'config', (_: undefined, xhr: XMLHttpRequest) => {
        xhr.setRequestHeader('X-HTTP-Method-Override', method);
      });

      options.method = 'POST';
    }

    // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.

    // @ts-expect-error Typescript doesn't know we return promisified `ReturnType` OR `string`,
    // so it errors due to Mithril's typings
    options.deserialize ||= (responseText: string) => responseText;

    options.errorHandler ||= (error) => {
      throw error;
    };

    // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.
    const original = options.modifyText || options.extract;

    // @ts-expect-error
    options.extract = (xhr: XMLHttpRequest) => {
      let responseText;

      if (original) {
        responseText = original(xhr.responseText);
      } else {
        responseText = xhr.responseText || null;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError(`${status}`, `${responseText}`, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        // @ts-expect-error
        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError('500', `${responseText}`, options, xhr);
      }
    };

    if (this.requestErrorAlert) this.alerts.dismiss(this.requestErrorAlert);

    // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.
    return m.request(options).then(
      (response) => response,
      (error) => {
        let content;

        switch (error.status) {
          case 422:
            content = (error.response.errors as Record<string, unknown>[])
              .map((error) => [error.detail, <br />])
              .flat()
              .slice(0, -1);
            break;

          case 401:
          case 403:
            content = app.translator.trans('core.lib.error.permission_denied_message');
            break;

          case 404:
          case 410:
            content = app.translator.trans('core.lib.error.not_found_message');
            break;

          case 413:
            content = app.translator.trans('core.lib.error.payload_too_large_message');
            break;

          case 429:
            content = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
            break;

          default:
            content = app.translator.trans('core.lib.error.generic_message');
        }

        const isDebug = app.forum.attribute('debug');
        // contains a formatted errors if possible, response must be an JSON API array of errors
        // the details property is decoded to transform escaped characters such as '\n'
        const errors = error.response && error.response.errors;
        const formattedError = Array.isArray(errors) && errors[0] && errors[0].detail && errors.map((e) => decodeURI(e.detail));

        error.alert = {
          type: 'error',
          content,
          controls: isDebug && [
            <Button className="Button Button--link" onclick={this.showDebug.bind(this, error, formattedError)}>
              {app.translator.trans('core.lib.debug_button')}
            </Button>,
          ],
        };

        try {
          options.errorHandler(error);
        } catch (error) {
          if (isDebug && error.xhr) {
            const { method, url } = error.options;
            const { status = '' } = error.xhr;

            console.group(`${method} ${url} ${status}`);

            console.error(...(formattedError || [error]));

            console.groupEnd();
          }

          this.requestErrorAlert = this.alerts.show(error.alert, error.alert.content);
        }

        return Promise.reject(error);
      }
    );
  }

  private showDebug(error: RequestError, formattedError?: string[]) {
    if (this.requestErrorAlert !== null) this.alerts.dismiss(this.requestErrorAlert);

    this.modal.show(RequestErrorModal, { error, formattedError });
  }

  /**
   * Construct a URL to the route with the given name.
   */
  route(name: string, params: Record<string, unknown> = {}): string {
    const route = this.routes[name];

    if (!route) throw new Error(`Route '${name}' does not exist`);

    const url = route.path.replace(/:([^\/]+)/g, (m, key) => `${extract(params, key)}`);

    // Remove falsy values in params to avoid having urls like '/?sort&q'
    for (const key in params) {
      if (params.hasOwnProperty(key) && !params[key]) delete params[key];
    }

    const queryString = m.buildQueryString(params as any);
    const prefix = m.route.prefix === '' ? this.forum.attribute('basePath') : '';

    return prefix + url + (queryString ? '?' + queryString : '');
  }
}
