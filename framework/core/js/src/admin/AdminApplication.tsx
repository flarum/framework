import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import routes, { AdminRoutes } from './routes';
import Application, { ApplicationData } from '../common/Application';
import Navigation from '../common/components/Navigation';
import AdminNav from './components/AdminNav';
import AdminRegistry from './utils/AdminRegistry';
import IHistory from '../common/IHistory';
import SearchManager from '../common/SearchManager';
import SearchState from '../common/states/SearchState';
import app from './app';
import BasicsPage from './components/BasicsPage';
import GeneralSearchIndex from './states/GeneralSearchIndex';
import AppearancePage from './components/AppearancePage';
import MailPage from './components/MailPage';
import AdvancedPage from './components/AdvancedPage';
import PermissionsPage from './components/PermissionsPage';

export interface Extension {
  id: string;
  name: string;
  version: string;
  description?: string;
  icon?: {
    name: string;
    [key: string]: string;
  };
  links: {
    authors?: {
      name?: string;
      link?: string;
    }[];
    discuss?: string;
    documentation?: string;
    support?: string;
    website?: string;
    donate?: string;
    source?: string;
  };
  extra: {
    'flarum-extension': {
      title: string;
      category?: string;
      'database-support'?: string[];
    };
  };
  require?: Record<string, string>;
}

export enum DatabaseDriver {
  MySQL = 'MySQL',
  PostgreSQL = 'PostgreSQL',
  SQLite = 'SQLite',
}

export interface AdminApplicationData extends ApplicationData {
  extensions: Record<string, Extension>;
  settings: Record<string, string>;
  modelStatistics: Record<string, { total: number }>;
  displayNameDrivers: string[];
  slugDrivers: Record<string, string[]>;
  searchDrivers: Record<string, string[]>;
  permissions: Record<string, string[]>;
  maintenanceByConfig: boolean;
  safeModeExtensions?: string[] | null;
  safeModeExtensionsConfig?: string[] | null;

  dbDriver: DatabaseDriver;
  dbVersion: string;
  dbOptions: Record<string, string>;
  phpVersion: string;
  queueDriver: string;
  schedulerStatus: string;
  sessionDriver: string;
}

export default class AdminApplication extends Application {
  /**
   * Stores the available settings, permissions, and custom pages of the app.
   * Allows the global search to find these items.
   *
   * @internal
   */
  registry = new AdminRegistry();

  extensionCategories = {
    feature: 30,
    theme: 20,
    language: 10,
  };

  history: IHistory = {
    canGoBack: () => true,
    getCurrent: () => null,
    getPrevious: () => null,
    push: () => {},
    backUrl: () => this.forum.attribute<string>('baseUrl'),
    back: function () {
      window.location.assign(this.backUrl());
    },
    home: () => {},
  };

  search: SearchManager<SearchState> = new SearchManager(new SearchState());

  /**
   * Custom settings and custom permissions do not go through the registry.
   * The general index is used to manually add these items to be picked up by the search.
   */
  generalIndex: GeneralSearchIndex = new GeneralSearchIndex();

  /**
   * Settings are serialized to the admin dashboard as strings.
   * Additional encoding/decoding is possible, but must take
   * place on the client side.
   *
   * @inheritdoc
   */

  data!: AdminApplicationData;

  route: typeof Application.prototype.route & AdminRoutes;

  constructor() {
    super();

    routes(this);

    this.route = (Object.getPrototypeOf(Object.getPrototypeOf(this)) as Application).route.bind(this);
  }

  protected beforeMount(): void {
    BasicsPage.register();
    AppearancePage.register();
    MailPage.register();
    AdvancedPage.register();
    PermissionsPage.register();
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

    m.mount(document.getElementById('app-navigation')!, {
      view: () => <Navigation className="App-backControl" drawer />,
    });
    m.mount(document.getElementById('header-navigation')!, Navigation);
    m.mount(document.getElementById('header-primary')!, HeaderPrimary);
    m.mount(document.getElementById('header-secondary')!, HeaderSecondary);
    m.mount(document.getElementById('admin-navigation')!, AdminNav);
  }

  getRequiredPermissions(permission: string) {
    const required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewForum');
    }
    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }
    if (permission === 'discussion.deletePosts') {
      required.push('discussion.hidePosts');
    }
    if (permission === 'user.editGroups') {
      required.push('viewHiddenGroups');
    }

    return required;
  }
}
