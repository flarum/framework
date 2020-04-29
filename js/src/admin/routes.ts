import BasicsPage from './components/BasicsPage';
import DashboardPage from './components/DashboardPage';

export default (app) => {
    app.routes = {
        dashboard: { path: '/', component: DashboardPage },
        basics: { path: '/basics', component: BasicsPage },
    };
};
