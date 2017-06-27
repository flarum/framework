import * as m from 'mithril';
import ItemList from 'flarum/utils/ItemList';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import RequestErrorModal from 'flarum/components/RequestErrorModal';
import ConfirmPasswordModal from 'flarum/components/ConfirmPasswordModal';
import Translator from 'flarum/Translator';
import extract from 'flarum/utils/extract';
import patchMithril from 'flarum/utils/patchMithril';
import RequestError from 'flarum/utils/RequestError';
import { extend } from 'flarum/extend';
import routes from 'flarum/routes';
import Header from 'flarum/components/Header';

/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
  constructor() {
    /**
     * [data description]
     *
     * @type {[type]}
     */
    this.data = null;

    /**
     * The forum model for this application.
     *
     * @type {Forum}
     * @public
     */
    this.forum = null;

    /**
     * [router description]
     *
     * @type {Router}
     */
    this.router = new Router();

    /**
     * The app's session.
     *
     * @type {Session}
     * @public
     */
    this.user = null;

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
    this.store = new Store();

    /**
     * A local cache that can be used to store data at the application level, so
     * that it persists between different routes.
     *
     * @type {Object}
     * @public
     */
    this.cache = {};

    /**
     * [title description]
     *
     * @type {DocumentTitle}
     */
    this.title = new DocumentTitle();

    /**
     * [ajax description]
     *
     * @type {Ajax}
     */
    this.ajax = new Ajax();
  }

  /**
   *
   */
  registerDefaultRoutes(router) {
  }

  /**
   * [loadModules description]
   *
   * @param {[type]} modules [description]
   * @return {[type]}
   */
  loadModules(modules) {

  }

  /**
   * Boot the application by running all of the registered initializers.
   *
   * @public
   */
  boot(data) {
    this.data = data;

    this.translator.addMessages(data.locale);

    this.store.push(data.resources);

    this.forum = new Forum(data.forum);

    this.user = this.store.find('users', data.userId);

    this.mount();
  }

  /**
   * Mount Flarum's interface onto the page.
   *
   * @return {[type]}
   * @protected
   */
  mount() {
    this.modal = m.mount(document.getElementById('modal'), <ModalManager/>);
    this.alerts = m.mount(document.getElementById('alerts'), <AlertManager/>);

    m.mount(document.getElementById('header'), <Header/>);
    m.route(document.getElementById('content'), '/', this.router.build());

    // Add a class to the body which indicates that the page has been scrolled
    // down.
    new ScrollListener(top => {
      const $app = $('#app');
      const offset = $app.offset().top;

      $app
        .toggleClass('affix', top >= offset)
        .toggleClass('scrolled', top > offset);
    }).start();
  }

  /**
   * Get the API response document that has been preloaded into the application.
   *
   * @return {Object|null}
   * @public
   */
  preloadedDocument() {
    if (this.data.document) {
      const results = this.store.sync(this.data.document);

      this.data.document = null;

      return results;
    }

    return null;
  }
}
