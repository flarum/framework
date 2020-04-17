import ItemList from './utils/ItemList';
import Alert from './components/Alert';
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
import { extend } from './extend';

import Forum from './models/Forum';
import User from './models/User';
import Discussion from './models/Discussion';
import Post from './models/Post';
import Group from './models/Group';
import Notification from './models/Notification';
import { flattenDeep } from 'lodash-es';

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
  /**
   * The forum model for this application.
   *
   * @type {Forum}
   * @public
   */
  forum = null;

  /**
   * A map of routes, keyed by a unique route name. Each route is an object
   * containing the following properties:
   *
   * - `path` The path that the route is accessed at.
   * - `component` The Mithril component to render when this route is active.
   *
   * @example
   * app.routes.discussion = {path: '/d/:id', component: DiscussionPage.component()};
   *
   * @type {Object}
   * @public
   */
  routes = {};

  /**
   * An ordered list of initializers to bootstrap the application.
   *
   * @type {ItemList}
   * @public
   */
  initializers = new ItemList();

  /**
   * The app's session.
   *
   * @type {Session}
   * @public
   */
  session = null;

  /**
   * The app's translator.
   *
   * @type {Translator}
   * @public
   */
  translator = new Translator();

  /**
   * The app's data store.
   *
   * @type {Store}
   * @public
   */
  store = new Store({
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
   *
   * @type {Object}
   * @public
   */
  cache = {};

  /**
   * Whether or not the app has been booted.
   *
   * @type {Boolean}
   * @public
   */
  booted = false;

  /**
   * An Alert that was shown as a result of an AJAX request error. If present,
   * it will be dismissed on the next successful request.
   *
   * @type {null|Alert}
   * @private
   */
  requestError = null;

  data;

  title = '';
  titleCount = 0;

  load(payload) {
    this.data = payload;
    this.translator.locale = payload.locale;
  }

  boot() {
    this.initializers.toArray().forEach((initializer) => initializer(this));

    this.store.pushPayload({ data: this.data.resources });

    this.forum = this.store.getById('forums', 1);

    this.session = new Session(this.store.getById('users', this.data.session.userId), this.data.session.csrfToken);

    this.mount();
  }

  bootExtensions(extensions) {
    Object.keys(extensions).forEach((name) => {
      const extension = extensions[name];

      const extenders = flattenDeep(extension.extend);

      for (const extender of extenders) {
        extender.extend(this, { name, exports: extension });
      }
    });
  }

  mount(basePath = '') {
    this.modal = m.mount(document.getElementById('modal'), <ModalManager />);
    this.alerts = m.mount(document.getElementById('alerts'), <AlertManager />);

    this.drawer = new Drawer();

    m.route(document.getElementById('content'), basePath + '/', mapRoutes(this.routes, basePath));

    // Add a class to the body which indicates that the page has been scrolled
    // down.
    new ScrollListener((top) => {
      const $app = $('#app');
      const offset = $app.offset().top;

      $app.toggleClass('affix', top >= offset).toggleClass('scrolled', top > offset);
    }).start();

    $(() => {
      $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
    });
  }

  /**
   * Get the API response document that has been preloaded into the application.
   *
   * @return {Object|null}
   * @public
   */
  preloadedApiDocument() {
    if (this.data.apiDocument) {
      const results = this.store.pushPayload(this.data.apiDocument);

      this.data.apiDocument = null;

      return results;
    }

    return null;
  }

  /**
   * Set the <title> of the page.
   *
   * @param {String} title
   * @public
   */
  setTitle(title) {
    this.title = title;
    this.updateTitle();
  }

  /**
   * Set a number to display in the <title> of the page.
   *
   * @param {Integer} count
   */
  setTitleCount(count) {
    this.titleCount = count;
    this.updateTitle();
  }

  updateTitle() {
    document.title = (this.titleCount ? `(${this.titleCount}) ` : '') + (this.title ? this.title + ' - ' : '') + this.forum.attribute('title');
  }

  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://lhorie.github.io/mithril/mithril.request.html
   * @param {Object} options
   * @return {Promise}
   * @public
   */
  request(originalOptions) {
    const options = Object.assign({}, originalOptions);

    // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.
    options.background = options.background || true;

    extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken));

    // If the method is something like PATCH or DELETE, which not all servers
    // and clients support, then we'll send it as a POST request with the
    // intended method specified in the X-HTTP-Method-Override header.
    if (options.method !== 'GET' && options.method !== 'POST') {
      const method = options.method;
      extend(options, 'config', (result, xhr) => xhr.setRequestHeader('X-HTTP-Method-Override', method));
      options.method = 'POST';
    }

    // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.
    options.deserialize = options.deserialize || ((responseText) => responseText);

    options.errorHandler =
      options.errorHandler ||
      ((error) => {
        throw error;
      });

    // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.
    const original = options.extract;
    options.extract = (xhr) => {
      let responseText;

      if (original) {
        responseText = original(xhr.responseText);
      } else {
        responseText = xhr.responseText || null;
      }

      const status = xhr.status;

      if (status < 200 || status > 299) {
        throw new RequestError(status, responseText, options, xhr);
      }

      if (xhr.getResponseHeader) {
        const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        return JSON.parse(responseText);
      } catch (e) {
        throw new RequestError(500, responseText, options, xhr);
      }
    };

    if (this.requestError) this.alerts.dismiss(this.requestError.alert);

    // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.
    const deferred = m.deferred();

    m.request(options).then(
      (response) => deferred.resolve(response),
      (error) => {
        this.requestError = error;

        let children;

        switch (error.status) {
          case 422:
            children = error.response.errors
              .map((error) => [error.detail, <br />])
              .reduce((a, b) => a.concat(b), [])
              .slice(0, -1);
            break;

          case 401:
          case 403:
            children = app.translator.trans('core.lib.error.permission_denied_message');
            break;

          case 404:
          case 410:
            children = app.translator.trans('core.lib.error.not_found_message');
            break;

          case 429:
            children = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
            break;

          default:
            children = app.translator.trans('core.lib.error.generic_message');
        }

        const isDebug = app.forum.attribute('debug');

        error.alert = new Alert({
          type: 'error',
          children,
          controls: isDebug && [
            <Button className="Button Button--link" onclick={this.showDebug.bind(this, error)}>
              Debug
            </Button>,
          ],
        });

        try {
          options.errorHandler(error);
        } catch (error) {
          this.alerts.show(error.alert);
        }

        deferred.reject(error);
      }
    );

    return deferred.promise;
  }

  /**
   * @param {RequestError} error
   * @private
   */
  showDebug(error) {
    this.alerts.dismiss(this.requestError.alert);

    this.modal.show(new RequestErrorModal({ error }));
  }

  /**
   * Construct a URL to the route with the given name.
   *
   * @param {String} name
   * @param {Object} params
   * @return {String}
   * @public
   */
  route(name, params = {}) {
    const url = this.routes[name].path.replace(/:([^\/]+)/g, (m, key) => extract(params, key));
    const queryString = m.route.buildQueryString(params);
    const prefix = m.route.mode === 'pathname' ? app.forum.attribute('basePath') : '';

    return prefix + url + (queryString ? '?' + queryString : '');
  }
}
