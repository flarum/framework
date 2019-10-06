import Application from '../common/Application';
import Layout from './components/Layout';

import IndexPage from './components/IndexPage';

export default class Forum extends Application {
    public routes = {
        index: { path: '/', component: IndexPage.component() },
    };

    get layout() {
        return Layout;
    }
}
