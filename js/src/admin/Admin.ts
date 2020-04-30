import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes from './routes';
import Application, {ApplicationData} from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';

type Extension = {
    description: string;
    extra: object;
    icon: {
        name: string;
    }
    id: number;
    version: string;
}

export type AdminData = ApplicationData & {
    mysqlVersion: string;
    phpVersion: string;
    extensions: {
        [key: string]: Extension;
    };
    permissions: {
        [key: string]: string[];
    };
    settings: {
        [key: string]: string;
    };
};

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

    data!: AdminData;

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
