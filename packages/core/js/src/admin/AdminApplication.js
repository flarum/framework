import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes from './routes';
import Application from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';

export default class AdminApplication extends Application {
  extensionSettings = {};

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

    m.mount(document.getElementById('app-navigation'), { view: () => Navigation.component({ className: 'App-backControl', drawer: true }) });
    m.mount(document.getElementById('header-navigation'), Navigation);
    m.mount(document.getElementById('header-primary'), HeaderPrimary);
    m.mount(document.getElementById('header-secondary'), HeaderSecondary);
    m.mount(document.getElementById('admin-navigation'), AdminNav);

    // If an extension has just been enabled, then we will run its settings
    // callback.
    const enabled = localStorage.getItem('enabledExtension');
    if (enabled && this.extensionSettings[enabled]) {
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
