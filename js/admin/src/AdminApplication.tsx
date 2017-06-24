import * as m from 'mithril';
import Application from './lib/Application';
import routes from './routes';
import Nav from './components/Nav';

export default class AdminApplication extends Application {
  /**
   * A map of extension names to their settings callbacks.
   *
   * @type {Object}
   */
  extensionSettings = {};

  /**
   * Construct a list of permissions required to have the given permission.
   *
   * @param {String} permission
   * @return {Array}
   */
  getRequiredPermissions(permission) {
    const required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewDiscussions');
    }
    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }
    if (permission === 'discussion.deletePosts') {
      required.push('discussion.editPosts');
    }

    return required;
  }

  /**
   * @inheritdoc
   */
  mount() {
    m.route.prefix('#');

    super.mount();

    m.mount(document.getElementById('nav'), <Nav/>);
  }

  /**
   * @inheritdoc
   */
  registerDefaultRoutes(router) {
    routes(router);
  }
}
