import DashboardPage from 'flarum/components/dashboard-page';
import BasicsPage from 'flarum/components/basics-page';
import PermissionsPage from 'flarum/components/permissions-page';
import AppearancePage from 'flarum/components/appearance-page';
import ExtensionsPage from 'flarum/components/extensions-page';

export default function(app) {
  app.routes = {
    'dashboard': ['/', DashboardPage.component()],
    'basics': ['/basics', BasicsPage.component()],
    'permissions': ['/permissions', PermissionsPage.component()],
    'appearance': ['/appearance', AppearancePage.component()],
    'extensions': ['/extensions', ExtensionsPage.component()]
  };
}
