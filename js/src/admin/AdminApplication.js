import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes from './routes';
import ExtensionPage from './components/ExtensionPage';
import Application from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';
import ExtensionData from './utils/ExtensionData';

export default class AdminApplication extends Application {
  // Deprecated as of beta 15
  extensionSettings = {};

  extensionData = new ExtensionData();

  extensionCategories = {
    discussion: 70,
    moderation: 60,
    feature: 50,
    formatting: 40,
    theme: 30,
    authentication: 20,
    language: 10,
    other: 0,
  };

  history = {
    canGoBack: () => true,
    getPrevious: () => {},
    backUrl: () => this.forum.attribute('baseUrl'),
    back: function () {
      window.location = this.backUrl();
    },
  };

  constructor() {
    super();

    routes(this);
  }

  /**
   * @inheritdoc
   */
  mount() {
    // Mithril does not render the home route on https://example.com/admin, so
    // we need to go to https://example.com/admin#/ explicitly.
    if (!document.location.hash) document.location.hash = '#/';

    m.route.prefix = '#';
    super.mount();

    m.mount(document.getElementById('app-navigation'), {
      view: () =>
        Navigation.component({
          className: 'App-backControl',
          drawer: true,
        }),
    });
    m.mount(document.getElementById('header-navigation'), Navigation);
    m.mount(document.getElementById('header-primary'), HeaderPrimary);
    m.mount(document.getElementById('header-secondary'), HeaderSecondary);
    m.mount(document.getElementById('admin-navigation'), AdminNav);

    // If an extension has just been enabled, then we will run its settings
    // callback.
    const enabled = localStorage.getItem('enabledExtension');
    if (enabled && this.extensionSettings[enabled] && typeof this.extensionSettings[enabled] === 'function') {
      this.extensionSettings[enabled]();
      localStorage.removeItem('enabledExtension');
    }
  }

  getRequiredPermissions(permission) {
    const required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewDiscussions');
    }
    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }
    if (permission === 'discussion.deletePosts') {
      required.push('discussion.hidePosts');
    }

    return required;
  }
}
