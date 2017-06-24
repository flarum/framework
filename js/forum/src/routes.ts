import DashboardPage from './components/DashboardPage';
import BasicsPage from './components/BasicsPage';
import PermissionsPage from './components/PermissionsPage';
import AppearancePage from './components/AppearancePage';
import ExtensionsPage from './components/ExtensionsPage';
import MailPage from './components/MailPage';

export default function(router) {
  router.add('dashboard', '/', DashboardPage);
  router.add('basics', '/basics', BasicsPage);
  router.add('permissions', '/permissions', PermissionsPage);
  router.add('appearance', '/appearance', AppearancePage);
  router.add('extensions', '/extensions', ExtensionsPage);
  router.add('mail', '/mail', MailPage);
}
