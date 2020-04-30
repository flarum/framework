import BasicsPage from './components/BasicsPage';
import DashboardPage from './components/DashboardPage';
import MailPage from './components/MailPage';
import PermissionsPage from './components/PermissionsPage';
import AppearancePage from './components/AppearancePage';

export default (app) => {
    app.routes = {
        dashboard: { path: '/', component: DashboardPage },
        basics: { path: '/basics', component: BasicsPage },
        mail: { path: '/mail', component: MailPage },
        permissions: { path: '/permissions', component: PermissionsPage },
        appearance: { path: '/appearance', component: AppearancePage },
    };
};
