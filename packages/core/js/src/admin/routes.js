import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import PermissionsPage from './components/PermissionsPage';
import AppearancePage from './components/AppearancePage';
import ExtensionsPage from './components/ExtensionsPage';
import MailPage from './components/MailPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function (app) {
  app.routes = {
    dashboard: { path: '/', component: DashboardPage },
    basics: { path: '/basics', component: BasicsPage },
    permissions: { path: '/permissions', component: PermissionsPage },
    appearance: { path: '/appearance', component: AppearancePage },
    extensions: { path: '/extensions', component: ExtensionsPage },
    mail: { path: '/mail', component: MailPage },
  };
}
