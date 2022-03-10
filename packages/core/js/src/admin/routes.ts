import AdminApplication from './AdminApplication';
import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import PermissionsPage from './components/PermissionsPage';
import AppearancePage from './components/AppearancePage';
import MailPage from './components/MailPage';
import UserListPage from './components/UserListPage';
import ExtensionPage from './components/ExtensionPage';
import ExtensionPageResolver from './resolvers/ExtensionPageResolver';

/**
 * The `routes` initializer defines the forum app's routes.
 */
export default function (app: AdminApplication) {
  app.routes = {
    dashboard: { path: '/', component: DashboardPage },
    basics: { path: '/basics', component: BasicsPage },
    permissions: { path: '/permissions', component: PermissionsPage },
    appearance: { path: '/appearance', component: AppearancePage },
    mail: { path: '/mail', component: MailPage },
    users: { path: '/users', component: UserListPage },
    extension: { path: '/extension/:id', component: ExtensionPage, resolverClass: ExtensionPageResolver },
  };
}
