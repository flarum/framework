import BasicsPage from './components/BasicsPage';
import DashboardPage from './components/DashboardPage';
import MailPage from './components/MailPage';

export default (app) => {
    app.routes = {
        dashboard: { path: '/', component: DashboardPage },
        basics: { path: '/basics', component: BasicsPage },
        mail: { path: '/mail', component: MailPage },
    };
};
