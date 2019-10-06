import m from 'mithril';

import Bus from './Bus';
import Translator from './Translator';

import extract from './utils/extract';
import mapRoutes from './utils/mapRoutes';

type ApplicationData = {
    apiDocument: any;
    locale: string;
    locales: any;
    resources: Array<any>;
    session: any;
};

export default abstract class Application {
    public data: ApplicationData | undefined;

    public translator = new Translator();
    public bus = new Bus();

    public routes = {};

    mount(basePath = '') {
        // this.modal = m.mount(document.getElementById('modal'), <ModalManager />);
        // this.alerts = m.mount(document.getElementById('alerts'), <AlertManager />);

        // this.drawer = new Drawer();

        m.mount(document.getElementById('header'), this.layout);

        m.route(document.getElementById('content'), basePath + '/', mapRoutes(this.routes, basePath));
    }

    abstract get layout();

    boot(payload: any) {
        this.data = payload;

        this.locale();
        this.plugins();
        this.mount();

        this.bus.dispatch('app.booting');
    }

    locale() {
        this.bus.dispatch('app.locale');
    }

    plugins() {
        this.bus.dispatch('app.plugins');
    }

    /**
     * Construct a URL to the route with the given name.
     */
    route(name: string, params: object = {}): string {
        const route = this.routes[name];

        if (!route) throw new Error(`Route ${name} does not exist`);

        const url = route.path.replace(/:([^\/]+)/g, (m, key) => extract(params, key));
        const queryString = m.buildQueryString(params);
        const prefix = ''; // TODO: use app base path
        // const prefix = m.route.mode === 'pathname' ? (app: any).forum.attribute('basePath') : '';

        return prefix + url + (queryString ? '?' + queryString : '');
    }
}