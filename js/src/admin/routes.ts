import DashboardPage from './components/DashboardPage';

export default (app) => {
    app.routes = {
        dashboard: { path: '/', component: DashboardPage },
    };
};
