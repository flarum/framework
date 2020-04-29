import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes from './routes';
import Application from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';

export default class Admin extends Application {
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
        m.mount(document.getElementById('app-navigation'), new Navigation({ className: 'App-backControl', drawer: true }));
        m.mount(document.getElementById('header-navigation'), new Navigation());
        m.mount(document.getElementById('header-primary'), new HeaderPrimary());
        m.mount(document.getElementById('header-secondary'), new HeaderSecondary());
        m.mount(document.getElementById('admin-navigation'), new AdminNav());

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
        const required: string[] = [];

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
