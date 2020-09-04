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
    m.mount(document.getElementById('app-navigation'), { view: () => Navigation.component({ className: 'App-backControl', drawer: true }) });
    m.mount(document.getElementById('header-navigation'), Navigation);
    m.mount(document.getElementById('header-primary'), HeaderPrimary);
    m.mount(document.getElementById('header-secondary'), HeaderSecondary);
    m.mount(document.getElementById('admin-navigation'), AdminNav);

    // With mithril 0.2.x, mithril redirects to the route with the hash automatically.
    // With 2.x, it does not do that, so it doesn't display the home route in the admin.
    // This code makes sure that going to https://example.com/admin takes us to the right page.
    if (!document.location.hash) document.location.hash = '#/';

    m.route.prefix = '#';

    super.mount();

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
