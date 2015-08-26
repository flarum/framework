import ItemList from 'flarum/utils/ItemList';
import Alert from 'flarum/components/Alert';
import Translator from 'flarum/Translator';
import extract from 'flarum/utils/extract';
import patchMithril from 'flarum/utils/patchMithril';

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class App {
  constructor() {
    patchMithril(window);

    /**
     * The forum model for this application.
     *
     * @type {Forum}
     * @public
     */
    this.forum = null;

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
    this.routes = {};

    /**
     * An object containing data to preload into the application.
     *
     * @type {Object}
     * @property {Object} preload.data An array of resource objects to preload
     *     into the data store.
     * @property {Object} preload.document An API response document to be used
     *     by the route that is first activated.
     * @property {Object} preload.session A response from the /api/token
     *     endpoint containing the session's authentication token and user ID.
     * @public
     */
    this.preload = {
      data: null,
      document: null,
      session: null
    };

    /**
     * An ordered list of initializers to bootstrap the application.
     *
     * @type {ItemList}
     * @public
     */
    this.initializers = new ItemList();

    /**
     * The app's session.
     *
     * @type {Session}
     * @public
     */
    this.session = null;

    /**
     * The app's translator.
     *
     * @type {Translator}
     * @public
     */
    this.translator = new Translator();

    /**
     * The app's data store.
     *
     * @type {Store}
     * @public
     */
    this.store = null;

    /**
     * A local cache that can be used to store data at the application level, so
     * that is persists between different routes.
     *
     * @type {Object}
     * @public
     */
    this.cache = {};

    /**
     * Whether or not the app has been booted.
     *
     * @type {Boolean}
     * @public
     */
    this.booted = false;

    /**
     * An Alert that was shown as a result of an AJAX request error. If present,
     * it will be dismissed on the next successful request.
     *
     * @type {null|Alert}
     * @private
     */
    this.requestError = null;

    this.title = '';
    this.titleCount = 0;
  }

  /**
   * Boot the application by running all of the registered initializers.
   *
   * @public
   */
  boot() {
    this.initializers.toArray().forEach(initializer => initializer(this));
  }

  /**
   * Get the API response document that has been preloaded into the application.
   *
   * @return {Object|null}
   * @public
   */
  preloadedDocument() {
    if (app.preload.document) {
      const results = app.store.pushPayload(app.preload.document);
      app.preload.document = null;

      return results;
    }

    return null;
  }

  /**
   * Set the <title> of the page.
   *
   * @param {String} title
   * @param {Boolean} [separator] Whether or not to separate the given title and
   *     the forum's title.
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
    document.title = (this.titleCount ? `(${this.titleCount}) ` : '') +
      (this.title ? this.title + ' - ' : '') +
      this.forum.attribute('title');
  }

  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://lhorie.github.io/mithril/mithril.request.html
   * @param {Object} options
   * @return {Promise}
   * @public
   */
  request(options) {
    // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.
    options.config = options.config || this.session.authorize.bind(this.session);
    options.background = options.background || true;

    // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.
    options.deserialize = options.deserialize || (responseText => {
      try {
        return JSON.parse(responseText);
      } catch (e) {
        throw new Error('Oops! Something went wrong on the server. Please reload the page and try again.');
      }
    });

    // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.
    const original = options.extract;
    options.extract = xhr => {
      const status = xhr.status;

      if (status >= 500 && status <= 599) {
        throw new Error('Oops! Something went wrong on the server. Please reload the page and try again.');
      }

      if (original) {
        return original(xhr.responseText);
      }

      return xhr.responseText.length > 0 ? xhr.responseText : null;
    };

    this.alerts.dismiss(this.requestError);

    // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.
    return m.request(options).then(null, response => {
      if (response instanceof Error) {
        this.alerts.show(this.requestError = new Alert({
          type: 'error',
          children: response.message
        }));
      }

      throw response;
    });
  }

  /**
   * Show alert error messages for each error returned in an API response.
   *
   * @param {Array} errors
   * @public
   */
  alertErrors(errors) {
    errors.forEach(error => {
      this.alerts.show(new Alert({
        type: 'error',
        children: error.detail
      }));
    });
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

  /**
   * Shortcut to translate the given key.
   *
   * @param {String} key
   * @param {Object} input
   * @return {String}
   * @public
   */
  trans(key, input) {
    return this.translator.trans(key, input);
  }
}
